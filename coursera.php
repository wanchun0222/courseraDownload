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

	private static $cookie = '/tmp/cookie.txt';

	private $lecturePage = '';

	private $lectureDir = '';

	private $lectureList = array();

	private $preDownloaded = array();

	private $curDownloaded = array();

	function __construct($lecPage = '', $lecDir = ''){
		$this->lecturePage = $lecPage;
		$this->lectureDir = $lecDir;
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

	public function getList(){

		$data = array();

		$curl = new LibCurl(true, self::$cookie);
		$listPageHtml = $curl->get($this->lecturePage);
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

				$titleArr = explode(' for ', $itemMatche[2][0]);
				$title = $titleArr[1];
				$data[$k][$title] = $itemMatche[1];

				echo "<pre>";
				print_r($data);

				$this->lectureList = $data;

				return $this;
				
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

					if (LibStorage::downloadFile($item, $dir . $fileName, self::$cookie)) {
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
		$progressFile = $this->lectureDir.'/progress.php';
		return file_put_contents($progressFile,$fileData , LOCK_EX);
	}

	public function error($msg = ''){
		die($msg);
	}

	private function _loadPreDownloaded(){
		$progressFile = $this->lectureDir.'/progress.php';
		LibStorage::mkdirs($this->lectureDir);
		if(file_exists($progressFile)){
			$this->preDownloaded = require_once($progressFile);
		}
	}

	private function _formatDir($dir){
		$dir = urldecode($dir);
		return str_replace(array(':','|','?','<','>','-'), array('：','_','','(',')','_'), $dir);
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

//中文文件名乱码问题
//特殊字符的目录生成问题
//想着参数的传递接收
//登录接口的稳定性

