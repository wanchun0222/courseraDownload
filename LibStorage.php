<?php

class LibStorage{

	/**
	 * 根据URL下载文件到指定的文件目录
	 *
	 * @param  string    $src    网络文件地址
	 * @param  string    $target 目标文件地址
	 * @return bool
	 */
	public static function downloadFile($src, $target){

		$ret = false;
		if (self::isWinOs()) {
			if ( $content = file_get_contents($src)) {
				$ret = file_put_contents($target, $content);
				unset($content);
			}
			return $ret;
		}else{
			$agent = '--user-agent="Mozilla/5.0 (Windows NT 6.1; rv:43.0) Gecko/20100101 Firefox/43.0"';
			exec('wget '.$agent.' -t 2 -T 5 "'.$src.'" -O '.$target, $out, $status);
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

		if(!file_exists( $path )){
			//mkdir($path, 0777, true); //权限上有点问题
			self::mkdirs(dirname($path));
			mkdir($path, 0777);
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

}