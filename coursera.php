<?php

set_time_limit(0);
ini_set('memory_limit', '128M');

require('./LibCurl.php');
require('./LibStorage.php');

class Coursera{

	const URL_PREFIX = 'https://www.coursera.org';

	const LIST_PREFIX = 'https://class.coursera.org';

	const USER_NAME = 'your coursera user name(email)';
	
	const PASSWORD = 'your coursera password';

	private $lecturePage = '';

	private $lectureDir = '';

	private $cookie = '';

	private $progress = '';

	private $lectureList = array();

	private $preDownloaded = array();

	function __construct($lecPage = '', $lecDir = ''){
		$this->lecturePage = $lecPage;
		$this->lectureDir  = $lecDir;
		$this->cookie      = $this->lectureDir . '/' . 'cookie.txt';
		$this->progress    = $this->lectureDir. '/' . 'progress.php';
	}

	public function login(){

		$loginPageUrl = self::URL_PREFIX . '/?authMode=login';
		$formCurl = new LibCurl(true, $this->cookie);
		$loginPageHtml = $formCurl->get($loginPageUrl);

		//登录
		$action = '';
		$matchStr = '#<form action="([^"]*)" method="post"#';
		preg_match($matchStr, $loginPageHtml,$actionM);
		if (is_array($actionM) && !empty($actionM[1])) {
			$action = trim($actionM[1]);
		}
		$loginCheckPage = self::URL_PREFIX . $action;
		$params = sprintf('email=%s&password=%s', self::USER_NAME, self::PASSWORD);
		$loginCurl = new LibCurl(true, $this->cookie);
		$loginCurl->post($loginCheckPage , $params);//{"message":"unauthorized.csrf"}

		$this->_reviseCookieFile();

		return $this;

	}

	public function getList(){

		$data = array();

		$curl = new LibCurl(true, $this->cookie);
		$listPageHtml = $curl->get($this->lecturePage);

		$chapterHtmlPreg = '#<ul class="course-item-list-section-list">(.+?)</ul>#s';
		preg_match_all($chapterHtmlPreg, $listPageHtml, $chapterHtmlMatche);
		$chapterHtmlArr = $chapterHtmlMatche[1];
		if (!is_array($chapterHtmlArr)) {
			$this->error('列表获取失败');
		}

		$chapterTitlePreg = '#<h3><span class="icon-chevron-(?:right|down)" style="width:18px;display:inline-block;"></span> &nbsp;([^<]+)</h3>#';
		preg_match_all($chapterTitlePreg, $listPageHtml, $chapterTitleMatche);
		$chapterTitleArr = $chapterTitleMatche[1];
		if (!is_array($chapterTitleArr)) {
			$this->error('列表获取失败');
		}

		if (empty($chapterHtmlArr) || count($chapterHtmlArr) != count($chapterTitleArr)) {
			$this->error('列表获取失败');
		}

		foreach ($chapterHtmlArr as $k => $chapterHtml) {

			$sectionPreg = '#<div class="course-lecture-item-resource">(.+?)</a>\s+</div>#s';
			preg_match_all($sectionPreg, $chapterHtml, $sectionMatche);

			$listHtmlArr = $sectionMatche[1];
			if (!is_array($listHtmlArr)) {
				continue;
			}
			foreach ($listHtmlArr as $listHtml) {

				$itemPreg = '#<a target="_new" href="([^"]+)".+?<div class="hidden">([^<]+)</div>#s';
				preg_match_all($itemPreg, $listHtml, $itemMatche);

				$titleArr = explode(' for ', $itemMatche[2][0]);
				$title = $titleArr[1];
				$data[$chapterTitleArr[$k]][$title] = $itemMatche[1];

				$this->lectureList = $data;
			}

		}

		return $this;
	}

	public function storageFile(){

		if (!is_array($this->lectureList)) {
			$this->error('列表为空');
		}

		$this->_loadPreDownloaded();

		foreach ($this->lectureList as $chapterKey => $chapterList) {

			$chapterDir = $this->_formatDir($chapterKey);

			foreach ($chapterList as $sectionKey => $sectionList) {
				
				$sectionDir = $this->_formatDir($sectionKey);

				foreach ($sectionList as $item) {

					$fileHash = md5($item);
					if (in_array($fileHash, array_keys($this->preDownloaded))) {
						continue;
					}

					$fileName = $this->_formatFileName($item);

					$dir = $this->lectureDir . '/' . $chapterDir . '/' . $sectionDir . '/';
					LibStorage::mkdirs($dir);

					if (LibStorage::downloadFile($item, $dir . $fileName, $this->cookie)) {
						$this->_saveProgress($fileHash."\t".$dir . $fileName.PHP_EOL);
						echo '==>file('.$dir . $fileName .') DONE'.PHP_EOL;
					}
				}

			}

		}

		return $this;

	}

	public function error($msg = ''){
		die($msg);
	}

	private function _saveProgress($line){

		if (empty($line)) {
			return false;
		}
		return file_put_contents($this->progress,$line, FILE_APPEND | LOCK_EX);
	}

	private function _loadPreDownloaded(){
		if(file_exists($this->progress)){
			$progressArr = @file($this->progress, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			if (!is_array($progressArr)) {
				return false;
			}

			foreach ($progressArr as $item) {
				list($fileHash, $file) = explode("\t", $item);
				$this->preDownloaded[$fileHash] = $file;
			}
		}
	}

	private function _formatDir($dir){
		$dir = urldecode($dir);
		$search = array(':','|','？','?','<','>','(',')','-',' ');
		$replace = array('_','_','','','（','）','（','）','_','');
		return str_replace($search, $replace, $dir);
	}

	private function _formatFileName($item){

		$item = urldecode($item);

		$itemArr = explode('/', $item);
		$fileName = array_pop($itemArr);

		$section = explode('?', $fileName);
		if (count($section) <= 1) {
			return $fileName;
		}else{

			list($file, $query) = $section;

			$param = preg_replace('#\w+=(\w+)&?#', '_\1', $query);
			$pos = strrpos($file, '.');
			if ($pos === false) {
				return $file . $param;
			}
			return substr($file, 0, $pos) . $param . substr($file, $pos);
		}
	}

	private function _reviseCookieFile(){
		if(file_exists($this->cookie)){
			$rightCookie = str_replace('#HttpOnly_','',file_get_contents($this->cookie));
			file_put_contents($this->cookie, $rightCookie);
		}
	}

}

list($file, $lecPage, $lecDir) = $argv;

if (empty($lecPage) || empty($lecDir)) {
	die('请传入2个参数：参数1为课程视频(Video Lectures)页面的URL,参数2为下载视频所保存路径(绝对路径)');
}

//$lecPage = 'https://class.coursera.org/os-001/lecture';
//$lecDir  = 'D:/tmp/lec';

$coursera = new Coursera($lecPage, $lecDir);
$coursera->login()->getList()->storageFile();

