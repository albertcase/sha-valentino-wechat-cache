<?php
class wechatfun{

  private $_TOKEN = null;
  private $_appid = null;
  private $_secret = null;
  private $_wechatinfo = null;
  private $_redis = null;

  public function __construct(){
    $this->_wechatinfo = require_once(dirname(__FILE__).'/wechatinfo.php');
    $this->_redis = new \Redis();
    $this->_redis->connect($this->_wechatinfo['redis_ip'], $this->_wechatinfo['redis_port']);
    $this->_TOKEN = $this->_wechatinfo['TOKEN'];
    $this->_appid = $this->_wechatinfo['appid'];
    $this->_secret = $this->_wechatinfo['secret'];
  }

  public function getJsSDK($url){
    $time = time();
    $ticket = $this->getWechatKey('access_ticket');
    $str = '1234567890abcdefghijklmnopqrstuvwxyz';
    $noncestr = '';
    for($i=0;$i<8;$i++){
      $randval = mt_rand(0,35);
      $noncestr .= $str[$randval];
    }
    $ticketstr = "jsapi_ticket=" . $ticket . "&noncestr=" . $noncestr . "&timestamp=" . $time . "&url=" . $url;
    $sign = sha1($ticketstr);
    $jssdk = array("appid" => $this->_appid,"time" => $time, "noncestr" => $noncestr, "sign" => $sign, "url" => $url);
    return array('code' => '10', 'msg' => 'success', 'jssdk' => $jssdk);
  }

  public function getWechatKey($key = 'access_token'){
    if(!in_array($key, array('access_token', 'access_ticket')))
      return false;
    return call_user_func_array(array($this, $key.'_Renew'), array());
  }

  public function access_token_Renew(){
    $access_token = $this->_redis->get($this->_wechatinfo['access_token']);
    if(!$access_token){
      $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET';
      $url = str_replace('APPID',$this->_appid ,$url);
      $url = str_replace('APPSECRET',$this->_secret ,$url);
      $rs = $this->get_data($url);
      if(isset($rs['access_token'])){
        $access_token = $rs['access_token'];
        $this->getTicket($access_token);
        $this->_redis->set($this->_wechatinfo['access_token'], $access_token);
        $this->_redis->setTimeout($this->_wechatinfo['access_token'], 5000);
        return $rs['access_token'];
      }else{
        return false;
      }
    }
    return $access_token;
  }

  public function access_ticket_Renew(){
    $access_ticket = $access_token = $this->_redis->get($this->_wechatinfo['access_ticket']);
    if(!$access_ticket){
      $this->access_token_Renew();
      $access_ticket = $access_token = $this->_redis->get($this->_wechatinfo['access_ticket']);
    }
    return $access_ticket;
  }

  public function getTicket($access_token){
    $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi';
    $url = str_replace('ACCESS_TOKEN', $access_token ,$url);
    $ticketfile = $this->get_data($url);
    $this->_redis->set($this->_wechatinfo['access_ticket'], $ticketfile['ticket']);
    $this->_redis->setTimeout($this->_wechatinfo['access_ticket'], 5000);
  }

  public function get_data($url, $return_array = true){
    if($return_array)
      return json_decode( file_get_contents($url), true );
    return file_get_contents($url);
  }

  public function post_data($url, $param, $is_file = false, $return_array = true){
    if (! $is_file && is_array ( $param )) {
    $param = $this->JSON ( $param );
    }
    if ($is_file) {
    $header [] = "content-type: multipart/form-data; charset=UTF-8";
    } else {
    $header [] = "content-type: application/json; charset=UTF-8";
    }
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
    curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
    curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
    curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
    curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $param );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    $res = curl_exec ( $ch );

    // 	$flat = curl_errno ( $ch );
    // 	if ($flat) {
    // 		$data = curl_error ( $ch );
    // 		addWeixinLog ( $flat, 'post_data flat' );
    // 		addWeixinLog ( $data, 'post_data msg' );
    // 	}

    curl_close ( $ch );

    if($return_array)
    $res = json_decode ( $res, true );
    return $res;
  }

  public function arrayRecursive(&$array, $function, $apply_to_keys_also = false) {
    static $recursive_counter = 0;
    if (++ $recursive_counter > 1000) {
    die ( 'possible deep recursion attack' );
    }
    foreach ( $array as $key => $value ) {
    if (is_array ( $value )) {
      $this->arrayRecursive ( $array [$key], $function, $apply_to_keys_also );
    } else {
      $array [$key] = $function ( $value );
    }

    if ($apply_to_keys_also && is_string ( $key )) {
      $new_key = $function ( $key );
      if ($new_key != $key) {
        $array [$new_key] = $array [$key];
        unset ( $array [$key] );
      }
    }
    }
    $recursive_counter --;
  }

  public function JSON($array) {
    $this->arrayRecursive ( $array, 'urlencode', true );
    $json = json_encode ( $array );
    return urldecode ( $json );
  }

}
