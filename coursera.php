<?php

set_time_limit(0);
ini_set('memory_limit', '128M');

require('./LibCurl.php');
require('./LibStorage.php');

class Coursera{

	const URL_PREFIX = 'https://www.coursera.org';

	const LIST_PREFIX = 'https://class.coursera.org';

	private static $userName = 'your coursera user name(email)';
	
	private static $password = 'your coursera password';

	private $lecturePage = '';

	private $lectureDir = '';

	private $cookie = '';

	private $progress = '';

	private $lectureList = array();

	private $preDownloaded = array();

	private $curDownloaded = array();

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
		$params = sprintf('email=%s&password=%s', self::$userName, self::$password);
		$loginCurl = new LibCurl(true, $this->cookie);
		$loginCurl->post( $loginCheckPage , $params);//{"message":"unauthorized.csrf"}

		return true;

	}

	public function getList(){

		$data = array();

		$curl = new LibCurl(true, $this->cookie);
		$listPageHtml = $curl->get($this->lecturePage);

		$chapterHtmlPreg = '#<ul class="course-item-list-section-list">(.+?)</ul>#s';
		preg_match_all($chapterHtmlPreg, $listPageHtml, $chapterHtmlMatche);
		$chapterHtmlArr = $chapterHtmlMatche[1];

		$chapterTitlePreg = '#<h3><span class="icon-chevron-(?:right|down)" style="width:18px;display:inline-block;"></span> &nbsp;([^<]+)</h3>#';
		preg_match_all($chapterTitlePreg, $listPageHtml, $chapterTitleMatche);
		$chapterTitleArr = $chapterTitleMatche[1];

		if (count($chapterHtmlArr) != count($chapterTitleArr)) {
			$this->error('列表获取失败');
		}

		foreach ($chapterHtmlArr as $k => $chapterHtml) {

			$sectionPreg = '#<div class="course-lecture-item-resource">(.+?)</a>\s+</div>#s';
			preg_match_all($sectionPreg, $chapterHtml, $sectionMatche);

			$listHtmlArr = $sectionMatche[1];
			foreach ($listHtmlArr as $listHtml) {

				$itemPreg = '#<a target="_new" href="([^"]+)".+?<div class="hidden">([^<]+)</div>#s';
				preg_match_all($itemPreg, $listHtml, $itemMatche);

				$titleArr = explode(' for ', $itemMatche[2][0]);
				$title = $titleArr[1];
				$data[$chapterTitleArr[$k]][$title] = $itemMatche[1];

				echo "<pre>";
				print_r($data);

				$this->lectureList = $data;

				return $this;//todo
				
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
						$this->curDownloaded[$fileHash] = $dir . $fileName;

						echo 'file('.$dir . $fileName .')download complete downloaded '.PHP_EOL;
					}
				}

			}

		}

		return $this;

	}

	public function saveProgress(){

		if (!$this->curDownloaded) {
			return false;
		}

		$fileData = '<?php return '. var_export($this->curDownloaded,true).';';
		return file_put_contents($this->progress,$fileData, LOCK_EX);
	}

	public function error($msg = ''){
		die($msg);
	}

	private function _loadPreDownloaded(){
		if(file_exists($this->progress)){
			$this->preDownloaded = require_once($this->progress);
		}
	}

	private function _formatDir($dir){
		$dir = urldecode($dir);
		return str_replace(array(':','|','？','?','<','>','-',' '), array('_','_','','','(',')','_',''), $dir);
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

}

$lecPage = 'https://class.coursera.org/os-001/lecture';
$lecDir  = 'D:/tmp/lec';

$coursera = new Coursera($lecPage, $lecDir);

if (!$coursera->login()) {
	die('login failed');
}

$coursera->getList()->storageFile()->saveProgress();
