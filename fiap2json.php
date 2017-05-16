<?php
/*
 * FIAP2JSON_GW
 *
 * Copyright 2017, Makoto Uju
 * Licensed under the MIT license.
 * Forked from miettal's FIAP2JSONP_GW(https://github.com/miettal/FIAP2JSONP_GW)
 */

function uuid(){
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

function send_response($json_array, $response_code = 200) {
  ob_start("ob_gzhandler");
  http_response_code($response_code);
  // header("Access-Control-Allow-Headers: Content-Type");
  header("Content-Type: application/json; charset=utf-8");
  header("Access-Control-Allow-Origin: *");
  echo json_encode($json_array);
  ob_end_flush();
}

function sanitize_to_array($object) {
  if( is_array($object) ) {
    return $object;
  } else {
    return array($object);
  }
}

if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  ob_start("ob_gzhandler");
  http_response_code(200);
  header("Access-Control-Allow-Headers: Access-Control-Allow-Origin, Access-Control-Allow-Methods, Content-Type");
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: POST, HEAD, OPTIONS");
  ob_end_flush();
}

$request_body = json_decode(file_get_contents('php://input'), true);
if($request_body == null) {
  echo json_encode(array("status" => "error", "error_msg" => "invalid query json"), 400);
  exit();
}

if(!isset($request_body["fiap_url"])){
  echo json_encode(array("status" => "error", "error_msg" => "fiap_url is required."), 400);
  exit();
}

$fiap_url = $request_body["fiap_url"];
$key = $request_body["keys"];

try {
  $fiap = new SoapClient($fiap_url."?wsdl",
    array('compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP));

  $cursor = null;
  $data = array();
  do{
    $result = $fiap->query(array(
        "transport" => array(
          "header" => array(
            "query" => array(
              "cursor" => $cursor,
              "type" => "storage",
              "id" => uuid(),
              "acceptableSize"=>"1000",
              "key" => $key,
            )
          )
        )
      )
    );

    if(isset($result->transport->body->point)){
      $point = $result->transport->body->point;
      $points = sanitize_to_array($point);

      foreach($points as $point){
        if(isset($point->value)){
          $point_id = $point->id;
          $values = sanitize_to_array($point->value);

          foreach($values as $v){
            $value = $v->_;
            $time = $v->time;
            array_push($data, array(
              'point_id' => $point_id,
              'time' => $time,
              'value' => $value));
          }
        }
      }
    }

    if(isset($result->transport->header->query->cursor)){
      $cursor = $result->transport->header->query->cursor;
    }else{
      $cursor = null;
    }
  }while($cursor !== null);

  send_response(array("status" => "success", "data" => $data));
} catch (Exception $e) {
  send_response(array("status" => "error", "error_msg" => $e->faultstring), 400);
}
