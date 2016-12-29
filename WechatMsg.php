<?php

class WechatMsg
{
  private $fromUsername;
  private $toUsername;

  public function __construct($fromUsername, $toUsername){
    $this->fromUsername = $fromUsername;
    $this->toUsername = $toUsername;
  }


  public function sendMsgxml($rs){
    if(method_exists($this, $rs[0]['MsgType'].'Msg')){
      return call_user_func_array(array($this, $rs[0]['MsgType'].'Msg'), array($this->xmlGet($rs)));
    }
    return false;
  }

//xmlarray start

  public function xmlGet($rs){
    if(method_exists($this, $rs[0]['MsgType'].'Xml')){
      return call_user_func_array(array($this, $rs[0]['MsgType'].'Xml'), array($rs));
    }
    return array(
      'MsgType' => $rs[0]['MsgType'],
      'ToUserName' => $this->fromUsername,
      'FromUserName' => $this->toUsername,
      'CreateTime' => time(),
      'MsgData' => json_decode($rs[0]['MsgData'], true),
    );
  }

  public function textXml($rs){
    return array(
      'MsgType' => $rs[0]['MsgType'],
      'ToUserName' => $this->fromUsername,
      'FromUserName' => $this->toUsername,
      'CreateTime' => time(),
      'MsgData' => json_decode($rs[0]['MsgData'], true),
    );
  }

  public function imageXml($rs){
    return array(
      'MsgType' => $rs[0]['MsgType'],
      'ToUserName' => $this->fromUsername,
      'FromUserName' => $this->toUsername,
      'CreateTime' => time(),
      'MsgData' => json_decode($rs[0]['MsgData'], true),
    );
  }

  public function voiceXml($rs){
    return array(
      'MsgType' => $rs[0]['MsgType'],
      'ToUserName' => $this->fromUsername,
      'FromUserName' => $this->toUsername,
      'CreateTime' => time(),
      'MsgData' => json_decode($rs[0]['MsgData'], true),
    );
  }

  public function videoXml($rs){
    return array(
      'MsgType' => $rs[0]['MsgType'],
      'ToUserName' => $this->fromUsername,
      'FromUserName' => $this->toUsername,
      'CreateTime' => time(),
      'MsgData' => json_decode($rs[0]['MsgData'], true),
    );
  }

  public function musicXml($rs){
    return array(
      'MsgType' => $rs[0]['MsgType'],
      'ToUserName' => $this->fromUsername,
      'FromUserName' => $this->toUsername,
      'CreateTime' => time(),
      'MsgData' => json_decode($rs[0]['MsgData'], true),
    );
  }

  public function newsXml($rs){
    return array(
      'MsgType' => $rs[0]['MsgType'],
      'ToUserName' => $this->fromUsername,
      'FromUserName' => $this->toUsername,
      'CreateTime' => time(),
      'MsgData' => json_decode($rs[0]['MsgData'], true),
    );
  }
//xmlarray end

  public function textMsg($data){
    if(!$this->checkData(array('Content'), $data['MsgData']))
      return false;
    $msg = "<xml>
<ToUserName><![CDATA[{$data['ToUserName']}]]></ToUserName>
<FromUserName><![CDATA[{$data['FromUserName']}]]></FromUserName>
<CreateTime>{$data['CreateTime']}</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[{$data['MsgData']['Content']}]]></Content>
</xml>";
    return $msg;
  }

  public function imageMsg($data){
    if(!$this->checkData(array('MediaId'), $data['MsgData']))
      return false;
    $msg = "<xml>
<ToUserName><![CDATA[{$data['ToUserName']}]]></ToUserName>
<FromUserName><![CDATA[{$data['FromUserName']}]]></FromUserName>
<CreateTime>{$data['CreateTime']}</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[{$data['MsgData']['MediaId']}]]></MediaId>
</Image>
</xml>";
    return $msg;
  }

  public function voiceMsg($data){
    if(!$this->checkData(array('MediaId'), $data['MsgData']))
      return false;
    $msg = "<xml>
<ToUserName><![CDATA[{$data['ToUserName']}]]></ToUserName>
<FromUserName><![CDATA[{$data['FromUserName']}]]></FromUserName>
<CreateTime>{$data['CreateTime']}</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
<Voice>
<MediaId><![CDATA[{$data['MsgData']['MediaId']}]]></MediaId>
</Voice>
</xml>";
    return $msg;
  }

  public function videoMsg($data){
    if(!$this->checkData(array('MediaId', 'Title', 'Description'), $data['MsgData']))
      return false;
    $msg = "<xml>
<ToUserName><![CDATA[{$data['ToUserName']}]]></ToUserName>
<FromUserName><![CDATA[{$data['FromUserName']}]]></FromUserName>
<CreateTime>{$data['CreateTime']}</CreateTime>
<MsgType><![CDATA[video]]></MsgType>
<Video>
<MediaId><![CDATA[{$data['MsgData']['MediaId']}]]></MediaId>
<Title><![CDATA[{$data['MsgData']['Title']}]]></Title>
<Description><![CDATA[{$data['MsgData']['Description']}]]></Description>
</Video>
</xml>";
    return $msg;
  }

  public function musicMsg($data){
    if(!$this->checkData(array('Title', 'Description', 'MusicUrl', 'HQMusicUrl', 'ThumbMediaId'), $data['MsgData']))
      return false;
    $msg = "<xml>
<ToUserName><![CDATA[{$data['ToUserName']}]]></ToUserName>
<FromUserName><![CDATA[{$data['FromUserName']}]]></FromUserName>
<CreateTime>{$data['CreateTime']}</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
<Music>
<Title><![CDATA[{$data['MsgData']['Title']}]]></Title>
<Description><![CDATA[{$data['MsgData']['Description']}]]></Description>
<MusicUrl><![CDATA[{$data['MsgData']['MusicUrl']}]]></MusicUrl>
<HQMusicUrl><![CDATA[{$data['MsgData']['HQMusicUrl']}]]></HQMusicUrl>
<ThumbMediaId><![CDATA[{$data['MsgData']['ThumbMediaId']}]]></ThumbMediaId>
</Music>
</xml>";
    return $msg;
  }

  public function newsMsg($data){
    if(!$this->checkData(array('Articles'), $data['MsgData']))
      return false;
    if(!is_array($data['MsgData']['Articles']) || !count($data['MsgData']['Articles']) || count($data['MsgData']['Articles']) > 10)
      return false;
    $ArticleCount = count($data['MsgData']['Articles']);
    $msg = "<xml>
<ToUserName><![CDATA[{$data['ToUserName']}]]></ToUserName>
<FromUserName><![CDATA[{$data['FromUserName']}]]></FromUserName>
<CreateTime>{$data['CreateTime']}</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>{$ArticleCount}</ArticleCount>
<Articles>";
    foreach($data['MsgData']['Articles'] as $item){
      if(!$this->checkData(array('Title', 'Description', 'PicUrl','Url'), $item))
        return false;
      $msg .= "<item>
<Title><![CDATA[{$item['Title']}]]></Title>
<Description><![CDATA[{$item['Description']}]]></Description>
<PicUrl><![CDATA[{$item['PicUrl']}]]></PicUrl>
<Url><![CDATA[{$item['Url']}]]></Url>
</item>";
    }
    $msg .= "</Articles>
</xml>";
    return $msg;
  }

//my self start

  // public function
//my self end
// sub function
  public function checkData($stand, $data){
    $keys = array_keys($data);
    foreach($stand as $x){
      if(!in_array($x, $keys))
        return false;
    }
    return true;
  }
}
