<?php

class WechatResponse{

  private $postStr = null;
  private $postObj = null;
  private $fromUsername = null;
  private $toUsername = null;
  private $msgType = null;
  private $dataSql;
  private $container;

  public function __construct($postStr){
    $this->postStr = $postStr;
    $this->postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    $this->msgType = strtolower($this->postObj->MsgType);
    $this->fromUsername = trim($this->postObj->FromUserName);
    $this->toUsername = $this->postObj->ToUserName;
  }

  public function RequestFeedback(){
    if(method_exists($this, $this->msgType.'Request')){
      $backxml =  call_user_func_array(array($this, $this->msgType.'Request'), array());
    }
    if($backxml)
        return $backxml;
    return $this->defaultfeedback();
  }

  public function msgResponse($rs){
    if(!isset($rs[0]['MsgType']))
      return false;
    $WechatMsg = new WechatMsg($this->fromUsername, $this->toUsername);
    return $WechatMsg->sendMsgxml($rs);
  }
//request functions start
  public function textRequest(){
    $rs = $this->textField(trim($this->postObj->Content));
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

  private function sendMsgForText($fromUsername, $toUsername, $time, $msgType, $contentStr)
  {
    $textTpl = "<xml>
          <ToUserName><![CDATA[%s]]></ToUserName>
          <FromUserName><![CDATA[%s]]></FromUserName>
          <CreateTime>%s</CreateTime>
          <MsgType><![CDATA[%s]]></MsgType>
          <Content><![CDATA[%s]]></Content>
          <FuncFlag>0</FuncFlag>
          </xml>";
    return sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
  }

  public function imageRequest(){
    return "";
  }

  public function voiceRequest(){
    return "";
  }

  public function videoRequest(){
    return "";
  }

  public function shortvideoRequest(){
    return "";
  }

  public function locationRequest(){
    // $this->systemLog();
    //LBS
    return $this->feedbackStores(trim($this->postObj->Location_X), trim($this->postObj->Location_Y));
  }

  public function linkRequest(){
    return "";
  }

  public function eventRequest(){
    $event = strtolower($this->postObj->Event);
    if(method_exists($this, $event.'Event')){
      return call_user_func_array(array($this, $event.'Event'), array());
    }
    return "";
  }
//request function end

//event function start
  public function subscribeEvent(){
    if(isset($this->postObj->Ticket)){//truck qrcode
      $rs = $this->qrcodeSubscribeTimes(trim($this->postObj->Ticket));
      return $this->msgResponse($rs);
    }
    $rs = $this->subscribeField();
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

  public function scanEvent(){
    if(isset($this->postObj->Ticket)){//truck qrcode
      $rs = $this->qrcodeSubscribeTimes(trim($this->postObj->Ticket));
      return $this->msgResponse($rs);
    }
    return "";
  }

  public function locationEvent(){
    return "";
  }

  public function location_selectEvent(){
    // $this->systemLog();
    return "";
  }

  public function clickEvent(){
    $rs = $this->clickField(trim($this->postObj->EventKey));
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

  public function viewEvent(){
    return "";
  }

//event function end
  public function systemLog(){
    // $this->dataSql->systemLog($this->postStr, $this->fromUsername, $this->msgType);
  }

  public function comfirmkeycode($msgType){

  }

  public function defaultfeedback(){
    $rs = $this->defaultField();
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

  //subfunction
  public function returnSquarePoint($lng, $lat,$distance = 0.5){
    $earthRadius = 6378138;
    $dlng =  2 * asin(sin($distance / (2 * $earthRadius)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);
    $dlat = $distance/$earthRadius;
    $dlat = rad2deg($dlat);
    return array(
                  'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
                  'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
                  'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
                  'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
    );
  }

  public function getDistance($lat1, $lng1, $lat2, $lng2){
    $earthRadius = 6378138; //近似地球半径米
    // 转换为弧度
    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;
    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;
    // 使用半正矢公式  用尺规来计算
    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
  }

  public function feedbackStores($l_x, $l_y){
    $baidu = file_get_contents("http://api.map.baidu.com/geoconv/v1/?coords={$l_x},{$l_y}&from=3&to=5&ak=Z5FOXZbjH3AEIukiiRTtD7Xy");
    $baidu = json_decode($baidu, true);
    $lat = $baidu['result'][0]['x'];
    $lng = $baidu['result'][0]['y'];
    $squares = $this->returnSquarePoint($lng,$lat,100000);
    $latbig = $squares['right-bottom']['lat'] > $squares['left-top']['lat'] ? $squares['right-bottom']['lat'] : $squares['left-top']['lat'];
    $latsmall = $squares['right-bottom']['lat'] > $squares['left-top']['lat'] ? $squares['left-top']['lat'] : $squares['right-bottom']['lat'];
    $lngbig = $squares['left-top']['lng'] > $squares['right-bottom']['lng'] ? $squares['left-top']['lng'] : $squares['right-bottom']['lng'];
    $lngsmall = $squares['left-top']['lng'] > $squares['right-bottom']['lng'] ? $squares['right-bottom']['lng'] : $squares['left-top']['lng'];
    $rs = $this->searchStore($latsmall, $latbig, $lngsmall, $lngbig);
    if(!$rs){
      return $this->sendMsgForText($this->fromUsername, $this->toUsername, time(), "text", '很抱歉，您的附近没有门店');
    }
    $datas = array();
    $data = array();
      for($i=0;$i<count($rs);$i++){
        $meter = $this->getDistance($lat,$lng,$rs[$i]['lat'],$rs[$i]['lng']);
        $meters = "(距离约" . $meter ."米)";
        $pisurl = 'source/change/store/'.$rs[$i]['id'].'.jpg';
        $datas[$meter] = array(
          'Title' => $rs[$i]['storename'].$meters,
          'Description' => $rs[$i]['storename'],
          'PicUrl' => 'http://'.$_SERVER['HTTP_HOST'].'/'.$pisurl,
          'Url' => 'http://'.$_SERVER['HTTP_HOST'].'/wechat/store/'.$rs[$i]['id'].'?orix='.$lat.'&oriy='.$lng,
        );
      }
    ksort($datas);
    $i=0;
    foreach($datas as $value){
      $data[$i] = $value;
      $i++;
    }
    $xml = array();
    $xml['0'] = array(
      "MsgType" => "news",
      "MsgData" => json_encode(array("Articles" => $data), JSON_UNESCAPED_UNICODE),
    );
    $WechatMsg = new WechatMsg($this->fromUsername, $this->toUsername);
    return $WechatMsg->sendMsgxml($xml);
  }

  //sub function
  private function textField($key){
    $list = $this->getfilecontainer(dirname(__FILE__).'./wechatcache/keywords.php');
    if(isset($list[$key])){
      return $this->getFeedbacks($list[$key]['menuId']);
    }
    return array();
  }

  private function getFeedbacks($key){
    $list = $this->getfilecontainer(dirname(__FILE__).'./wechatcache/feedbacks.php');
    $result = array();
    if(isset($list[$key]))
      $result[0] = $list[$key];
    return $result;
  }

  private function qrcodeSubscribeTimes($key){
    $list = $this->getfilecontainer(dirname(__FILE__).'./wechatcache/qrcodes.php');
    if(isset($list[$key]))
      return $this->getFeedbacks($list[$key]['feedbackid']);
    return array();
  }

  private function subscribeField(){
    $list = $this->getfilecontainer(dirname(__FILE__).'./wechatcache/events.php');
    if(isset($list['subscribe']))
      return $this->getFeedbacks($list['subscribe']['menuId']);
    return array();
  }

  private function clickField($key){
    $list = $this->getfilecontainer(dirname(__FILE__).'./wechatcache/events.php');
    if(isset($list[$key]))
      return $this->getFeedbacks($list[$key]['menuId']);
    return array();
  }

  private function defaultField(){
    $list = $this->getfilecontainer(dirname(__FILE__).'./wechatcache/events.php');
    if(isset($list['defaultback']))
      return $this->getFeedbacks($list['defaultback']['menuId']);
    return array();
  }

  private function searchStore($latsmall, $latbig, $lngsmall, $lngbig){
    $list = $this->getfilecontainer(dirname(__FILE__).'./wechatcache/stores.php');
    $result = array();
    if($list){
      foreach ($list as $value) {
        if(($value['lat'] > $latsmall && $value['lat'] < $latbig) && ($value['lng'] > $lngsmall && $value['lng'] < $lngbig))
          $result[] = $value;
      }
    }
    return $result;
  }


  private function getfilecontainer($path){
    if(file_exists($path))
      return require_once($path);
    return array();
  }

}
