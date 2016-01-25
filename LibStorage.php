<?php

class LibStorage{

	private static $streamContext = null;

	/**
	 * 根据URL下载文件到指定的文件目录
	 *
	 * @param  string    $src    网络文件地址
	 * @param  string    $target 目标文件地址
	 * @return bool
	 */
	public static function downloadFile($src, $target, $cookie){

		$ret = false;
		if (self::isWinOs()) {

			if (is_null(self::$streamContext)) {
				self::$streamContext = self::createStreamContext($cookie);
			}

			if (self::$streamContext === false) {
				return $ret;
			}

			if ( $content = file_get_contents($src, false, self::$streamContext)) {
				
				$target = iconv('utf-8', 'gbk', $target);
				$ret = file_put_contents($target, $content);
				unset($content);
			}
			return $ret;
		}else{
			$agent = '--user-agent="Mozilla/5.0 (Windows NT 6.1; rv:43.0) Gecko/20100101 Firefox/43.0"';
			$cookieParam = '--load-cookies='.$cookie;
			exec('wget '.$agent.' '.$cookieParam.' -t 2 -T 5 "'.$src.'" -O '.$target, $out, $status);
			return file_exists($target) && filesize($target);
		}
	}

	/**
	 * 递归生成给定路径的目录并设置权限
	 *
	 * @param  string    $path
	 * @return void
	 */
	public static function mkdirs($path){

		if (self::isWinOs()) {
			$path = iconv('utf-8', 'gbk', $path);
		}

		if(!file_exists( $path )){
			//mkdir($path, 0777, true); //权限上有点问题
			self::mkdirs(dirname($path));
			$dd = mkdir($path, 0777);
			var_dump($dd);
			chmod($path, 0777); 
		}
	}

	/**
	 * 是否是WIN系列操作系统
	 *
	 * @param  void
	 * @return bool
	 */
	public static function isWinOs(){
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	/**
	 * 创建流上下文
	 *
	 * @param  string $cookie curl库生成的cookie文件
	 * @return bool|object 
	 */
	public static function createStreamContext($cookie){

		$cookieArr = @file($cookie, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!is_array($cookieArr)) {
			return false;
		}

		$cookieStr = '';
		foreach ($cookieArr as $item) {
			$cookieSection = explode("\t", $item);
			if (count($cookieSection) == 7) {
				$val = array_pop($cookieSection);
				$key = array_pop($cookieSection);
				$cookieStr .= $key.'='.$val.';';
			}
		}

		if (empty($cookieStr)) {
			return false;
		}

		$opts =  array (
			'http' => array (
				'method' => "GET",
				'header' => "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:43.0) Gecko/20100101 Firefox/43.0\r\n".
							"Accept: */*\r\n".
							"Cookie: " . $cookieStr
			)
		);
		return stream_context_create($opts);
	}

}
