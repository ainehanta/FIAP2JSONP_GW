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

if(!isset($_GET["fiap_url"]) ||
   !isset($_GET["key"])){
  echo json_encode(array("status" => "error", "error_msg" => "fiap_url and key are required."), 400);
  exit();
}

$fiap_url = $_GET["fiap_url"];
$key = $_GET["key"];

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
