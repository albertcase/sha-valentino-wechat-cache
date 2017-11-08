<?php

$conf = require_once __DIR__.'/jssdkconf.php';

if(!isset($_SERVER['REQUEST_URI'])) {
    exit('param is not exist');
}

$routeArr = explode('/', $_SERVER['REQUEST_URI']);
$id = $routeArr['4'];

if(!in_array($id, $conf['jssdk_id_list'])) {
    exit('jssdk id is not exist');
}

if(isset($_SERVER['HTTP_REFERER']))
    $url = $_SERVER['HTTP_REFERER'];
else
    $url = isset($_GET['referer']) ? urldecode($_GET['referer']) : '';

header("Content-type:application/javascript");
$jssdk = new Jssdk($conf);
print_r($jssdk->getJssdk($url));
exit;

class Jssdk
{
    private $redis;
    private $conf;
    const WECHAT_JSSDK_KEY = 'wechat:token:prefix:jsapi_ticket';
    const WECHAT_RETRIEVE_JSSDK = 'http://valentinowechat.samesamechina.com/wechat/retrieve/jsapi_ticket';

    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->redis = new \Redis();
        $this->redis->connect($conf['redis_host'], $conf['redis_port']); 
    }

    private function getTicket()
    {
        if($ticket = $this->redis->get(self::WECHAT_JSSDK_KEY)) {
            return $ticket;
        } else {
            $url = self::WECHAT_RETRIEVE_JSSDK;
            $data = file_get_contents($url);
            $data = json_decode($data);
            return $data->data;
        }
    }

    public function getJssdk($url)
    {
        $key = 'jssdk:long:'.$url;
        if($key_value = $this->redis->get($key)) {
            return $key_value;
        } else {
            $time = time();
            $ticket = $this->getTicket();
            $noncestr = 'QAdhsWE';
            $ticketstr = "jsapi_ticket=" . $ticket . "&noncestr=" . $noncestr . "&timestamp=" . $time . "&url=" . $url;
            $sign = sha1($ticketstr);
            $jsConfig = array(
              'debug' => (isset($_GET['debug']) && $_GET['debug']) ? true : false,
              'appId' => $this->conf['appid'],
              'timestamp' => $time,
              'nonceStr' => $noncestr,
              'signature' => $sign,
              'jsApiList' => $this->conf['jssdk_api_list'],
            );
            $jsConfig = json_encode($jsConfig, JSON_UNESCAPED_UNICODE);
$key_value = <<<EOF
var script = document.createElement('script');
script.onload = function() {
  wx.config({$jsConfig});
};
script.src = "https://res.wx.qq.com/open/js/jweixin-1.0.0.js";
document.getElementsByTagName('head')[0].appendChild(script);
EOF;
              $redis->set($key, $key_value);
              $redis->setTimeout($key, 3600);
              return $key_value;
        }
  }

}

?>