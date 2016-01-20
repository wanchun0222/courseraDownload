<?php

set_time_limit(0);

require('./LibCurl.php');
require('./LibStorage.php');

class Coursera{

	const URL_PREFIX = 'https://www.coursera.org';

	const LIST_PREFIX = 'https://class.coursera.org';

	private static $userName = 'your coursera user name(email)';
	
	private static $password = 'your coursera password';

	private static $cookie = '/tmp/cookie.txt';

	private $lecturePage = '';

	private $lectureList = [];

	function __construct($lecPage = ''){
		$this->lecturePage = $lecPage;
	}

	public function login(){

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
		$loginCheckPage = self::URL_PREFIX . $action;
		$params = sprintf('email=%s&password=%s', self::$userName, self::$password);
		$loginCurl = new LibCurl(true, self::$cookie);
		$loginCurl->post( $loginCheckPage , $params);//{"message":"unauthorized.csrf"}

		return true;

	}

	public function lists(){

		$listPageUrl = self::LIST_PREFIX . $this->lecturePage;
		$curl = new LibCurl(true, self::$cookie);
		$listPageHtml = $curl->get($listPageUrl);

		$data = array();

		$chapterPreg = '#<ul class="course-item-list-section-list">(.+?)</ul>#s';
		preg_match_all($chapterPreg, $listPageHtml, $chapterMatche);
		$chapterHtmlArr = $chapterMatche[1];

		foreach ($chapterHtmlArr as $k => $chapterHtml) {

			$sectionPreg = '#<div class="course-lecture-item-resource">(.+?)</a>\s+</div>#s';
			preg_match_all($sectionPreg, $chapterHtml, $sectionMatche);

			$listHtmlArr = $sectionMatche[1];

			foreach ($listHtmlArr as $listHtml) {

				$itemPreg = '#<a target="_new" href="([^"]+)".+?<div class="hidden">([^<]+)</div>#s';
				preg_match_all($itemPreg, $listHtml, $itemMatche);

				$title = explode(' for ', $itemMatche[2][0])[1];
				$data[$k][$title] = $itemMatche[1];
				
			}

		}

		$this->lectureList = $data;

		print_r($data);

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

