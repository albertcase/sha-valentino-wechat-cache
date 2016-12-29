<?php
require_once dirname(__FILE__).'/WechatMsg.php';
require_once dirname(__FILE__).'/WechatResponse.php';

if(isset($_GET["echostr"])){
  header("Content-type:text/xml");
  $_wechatinfo = require_once(dirname(__FILE__).'/wechatinfo.php');
  $signature = $_GET["signature"];
  $timestamp = $_GET["timestamp"];
  $nonce = $_GET["nonce"];
  $token = $_wechatinfo['TOKEN'];
  $tmpArr = array($token, $timestamp, $nonce);
  sort($tmpArr, SORT_STRING);
  $tmpStr = implode( $tmpArr );
  $tmpStr = sha1( $tmpStr );

  if( $tmpStr == $signature ){
    print_r($_GET["echostr"]);
  }else{
    print "";
  }
  exit;
}

header("Content-type:text/xml");
$xmlObj = isset($GLOBALS["HTTP_RAW_POST_DATA"])?$GLOBALS["HTTP_RAW_POST_DATA"]:'';
$response = new WechatResponse($xmlObj);
print_r($response->RequestFeedback());
exit;
?>
