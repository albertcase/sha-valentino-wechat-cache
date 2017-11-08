<?php

$conf = require_once __DIR__.'/jssdkconf.php';

$oauth = new Oauth($conf);

$code = isset($_GET['code']) ? $_GET['code'] : false;
# 微信授权callback
if($code) {
	$oauth->callbackApi($code);
}

$scope = isset($_GET['scope']) ? $_GET['scope'] : 'snsapi_base';
$state = isset($_GET['state']) ? $_GET['state'] : '';

$redirectUrl = $_GET['redirect_uri'];
# 没有回调地址报错
if(!$redirectUrl) {
	exit('parameter redirect url failed');
}

# 验证授权域名
if($oauth->checkdomain($redirectUrl)){
	exit('oauth domain is not exists');
}

$callbackUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] . '?redirect_uri=' . urlencode($redirect_uri) . '&scope=' . $scope;
# 微信授权
$oauth->oauthApi($callbackUrl, $scope, $state);

class Oauth
{
	private $conf;
	const OAUTH_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s#wechat_redirect';
	const OAUTH_ACCESS_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';

	public function __construct($conf)
	{
		$this->conf = $conf;
	}

	# 微信授权
	public function oauthApi($url, $scope, $state)
	{
		$url = $this->getoauth2url(urlencode($url), $scope, $state);
		header('Location:' . $url);
	}

	# 微信授权callback
	public function callbackApi($code)
	{
		$callback_url = $_GET['redirect_uri'];
		if($code) {
			$access_token = $this->getOauthToken($code);
		  	$param = array();
		  	if(isset($access_token['openid'])) {
		    	if($access_token['scope'] == 'snsapi_base') {
		      		$param['openid'] = $access_token['openid'];
		    	} 
		    if($access_token['scope'] == 'snsapi_userinfo') {
		      		$param['openid'] = $access_token['openid'];
		      		$param['access_token'] = $access_token['access_token'];
		    	}
			}
		  	$url = $this->generateRedirectUrl($callback_url, $param);
			header('Location:' . $url);
		} else {
			return false;
		}
	}

	# 通过授权返回的code换取授权access_token
	private function getOauthToken($code)
	{
		$url = sprintf(self::OAUTH_ACCESS_TOKEN, $this->conf['appid'], $this->conf['secret'], $code);
		$res = $this->callbackApi($url);
		return $res->access_token;
	}

	# wechat api
	private function callWechatApi($src) {
      	$data = file_get_contents($src);
      	$data = json_decode($data);
      	return $data;
  	}

	# 获取授权接口URL
	private function getoauth2url($redirectUrl, $scope, $state)
	{
		if(!in_array($snsapi, array('snsapi_userinfo', 'snsapi_base')))
			return false;
		$url = sprintf(self::OAUTH_URL, $this->conf['appid'], $redirectUrl, $scope, $state);
		return $url;
	}

	# 判断授权域名是否合法
	private function checkdomain($domain)
	{
		$parse_url = parse_url($url);
        return in_array($parse_url['host'], $this->conf['oauth_domain']);
	}

	# 格式化url
	private function generateRedirectUrl($url, $param) 
	{
	    $parse_url = parse_url(urldecode($url));
	    $base = $parse_url['scheme'] . '://' . $parse_url['host'] . $parse_url['path'];
	    if(isset($parse_url['query'])) {
	      parse_str($parse_url['query'], $query);
	      $param = array_merge($query, $param);
	    }
	    return $base . '?' . http_build_query($param);
  	}	
}
