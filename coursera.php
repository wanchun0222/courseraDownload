<?php

set_time_limit(0);

require('./LibCurl.php');
require('./LibStorage.php');


function test(){

	set_time_limit(0);

	$cookie = '/tmp/cookie.txt';

	$urlForm = 'https://www.coursera.org/?authMode=login';
	$formCurl = new LibCurl(true, $cookie);
	$content = $formCurl->get( $urlForm);

	//登录
	$matchStr = '#<form action="([^"]*)" method="post"#';
	preg_match($matchStr, $content,$actionM);
	echo $action = trim($actionM[1]);
	$loginPre = 'https://www.coursera.org'.$action;
	$param = 'email=wanchun0222@126.com&password=014622';
	$loginCurl = new LibCurl(true, $cookie);
	$loginCurl->post( $loginPre , $param);

	//获取列表数据
	$curPage = 'https://class.coursera.org/pkujava-001/lecture';
	$listCurl = new LibCurl(true, $cookie);
	$html = $listCurl->get( $curPage);

	$matchStr = '#data\-lecture\-id="([^"]*)"\s*data\-link\-type="lecture:download\.mp4"\s*data\-placement\="top"\s*rel="tooltip"\s*title="Video \(MP4\)" >\s*<i class="icon\-download\-alt resource" ></i>\s*<div class="hidden">([^<]*)</div>#';
	preg_match_all($matchStr, $html,$lectureM);
	
	echo '<pre>';
	print_r($lectureM);
	echo '</pre>';

}
//test();
//exit;
class Coursera{

	const URL_PREFIX = 'https://www.coursera.org';
	const LIST_PREFIX = 'https://class.coursera.org';

	private static $userName = 'wanchun0222@126.com';
	
	private static $password = '014622';

	private static $cookie = '/tmp/cookie.txt';

	private $lecturePage = '';

	private $lectureList = [];

	function __construct($lecPage = ''){
		$this->lecturePage = $lecPage;
	}

	public function login(){

		/*//$loginPageUrl = self::URL_PREFIX . '/?authMode=login';
		$loginPageUrl = 'https://www.coursera.org/?authMode=login';
		//$loginPageHtml = file_get_contents($loginPageUrl);
		$formCurl = new LibCurl(true, self::$cookie);
		$loginPageHtml = $formCurl->get($loginPageUrl);
		//$curl = new LibCurl(true, self::$cookie);
		//$loginPageHtml = $curl->get($loginPageUrl);


		//获取登录地址串
		$action = '';
		$matchStr = '#<form action="([^"]*)" method="post"#';
		preg_match($matchStr, $loginPageHtml,$actionM);
		if (is_array($actionM) && !empty($actionM[1])) {
			$action = trim($actionM[1]);
		}
		if (empty($action)) {
			$this->error('登录失败');
		}

		echo $loginCheckPage = self::URL_PREFIX . $action;
		echo $params = sprintf('email=%s&password=%s', self::$userName, self::$password);
		$curl = new LibCurl(true, self::$cookie);
		echo $ret = $curl->post( $loginCheckPage , $params);

		return ;*/


		$loginPageUrl = self::URL_PREFIX . '/?authMode=login';
		$formCurl = new LibCurl(true, self::$cookie);
		$loginPageHtml = $formCurl->get($loginPageUrl);

		//登录
		$action = '';
		$matchStr = '#<form action="([^"]*)" method="post"#';
		preg_match($matchStr, $loginPageHtml,$actionM);
		if (is_array($actionM) && !empty($actionM[1])) {
			$action = trim($actionM[1]);
		}
		if (empty($action)) {
			$this->error('登录失败');
		}
		//$action = trim($actionM[1]);
		$loginCheckPage = self::URL_PREFIX . $action;
		$params = sprintf('email=%s&password=%s', self::$userName, self::$password);
		$loginCurl = new LibCurl(true, self::$cookie);
		echo $loginCurl->post( $loginCheckPage , $params);//{"message":"unauthorized.csrf"}

		return true;

	}

	public function lists(){

		$listPageUrl = self::LIST_PREFIX . $this->lecturePage;
		$curl = new LibCurl(true, self::$cookie);
		$listPageHtml = $curl->get($listPageUrl);

		$pregStr = '#data\-lecture\-id="([^"]*)"\s*data\-link\-type="lecture:download\.mp4"\s*data\-placement\="top"\s*rel="tooltip"\s*title="Video \(MP4\)" >\s*<i class="icon\-download\-alt resource" ></i>\s*<div class="hidden">([^<]*)</div>#';
		preg_match_all($pregStr, $listPageHtml,$lectureM);
		
		echo '<pre>';
		print_r($lectureM);
		echo '</pre>';

		$this->lectureList = $lectureM;

		return $this;
	}

	public function storage(){

		if (!is_array($this->lectureList)) {
			$this->error('列表为空');
		}

		foreach ($this->lectureList as $k => $v) {
			# code...
		}

	}

	public function error($msg = ''){
		die($msg);
	}

	private function _progress(){
		
	}

}

$lecPage = '/pkujava-001/lecture';

$coursera = new Coursera($lecPage);

if (!$coursera->login()) {
	die('login failed');
}

$coursera->lists()->storage();