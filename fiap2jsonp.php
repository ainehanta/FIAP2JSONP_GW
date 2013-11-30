<?php
/*
 * FIAP2JSONP_GW
 * https://github.com/miettal/FIAP2JSONP_GW
 *
 * Copyright 2012, "miettal" Hiromasa Ihara
 * Licensed under the MIT license.
 */
header("Content-Type: text/javascript; charset=utf-8");

if(!isset($_GET["callback"])){
  exit();
}
$callback = $_GET["callback"];

if(!isset($_GET["fiap_url"]) ||
   !isset($_GET["key"])){
  echo "$callback(".json_encode(array("status" => "error",
    "error_msg" => "fiap_url and key are required.")).")";
  exit();
}

$fiap_url = $_GET["fiap_url"];
$key = $_GET["key"];

try {
  $fiap = new SoapClient($fiap_url."?wsdl");

  $cursor = null;
  $data = array();
  do{
    $result = $fiap->query(array(
        "transport" => array(
          "header" => array(
            "query" => array(
              "cursor" => $cursor,
              "type" => "storage",
              "id" => uuid_create(),
              "acceptableSize"=>"100",
              "key" => $key,
            )
          )
        )
      )
    );
    
    if(isset($result->transport->body->point)){
      $point = $result->transport->body->point;
      if(is_array($point)){
        $points = $point;
      }else{
        $points = array($point);
      }
      foreach($points as $point){
        if(isset($point->value)){
          $point_id = $point->id;
          if(is_array($point->value)){
            $values = $point->value;
          }else{
            $values = array($point->value);
          }
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
} catch (SoapFault $e) {
  echo "$callback(".json_encode(array("status" => "error",
    "error_msg" => $e->faultstring)).")";
  exit();
}

echo "$callback(".json_encode(array("status" => "success", "data" => $data)).")";
