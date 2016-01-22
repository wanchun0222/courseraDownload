<?php

class LibCurl{

	public $headers;
	public $user_agent;
	public $compression;
	public $cookie_file;
	public $proxy;

	public function __construct($cookies = TRUE, $cookie = 'cookies.txt', $compression = 'gzip', $proxy = ''){
		$this->headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';
		$this->headers[] = 'Connection: Keep-Alive';
		$this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
		$this->user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:43.0) Gecko/20100101 Firefox/43.0';
		$this->compression = $compression;
		$this->proxy = $proxy;
		$this->cookies = $cookies;
		if ($this->cookies == TRUE){
			$this->cookie($cookie);
		}
	}

	public function cookie($cookie_file){
		if (file_exists($cookie_file)) {
			$this->cookie_file = $cookie_file;
		} else {
			$fp = fopen($cookie_file, 'w') or die('The cookie file could not be opened. Make sure this directory has the correct permissions');
			$this->cookie_file = $cookie_file;
			if (is_resource($fp))
			{
				fclose($fp);
			}
		}
	}

	public function get($url){
		$process = curl_init();
		curl_setopt($process, CURLOPT_URL,$url);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE){
			curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
			curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		}
		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		if ($this->proxy){
			curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		}
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}

	public function post($url, $data){
		$process = curl_init();
		curl_setopt($process, CURLOPT_URL,$url);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE){
			curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
			curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		}
		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		if ($this->proxy){
			curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		}
		curl_setopt($process, CURLOPT_POSTFIELDS, $data);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_POST, 1);
		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}
	
}

