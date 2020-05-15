<?php

class cURL
{

	/// Routines for URL-fetching
	var $headers;
	var $user_agent;
	var $compression;
	var $cookie_file;
	var $proxy;
	var $cookies;
	var $ip;
	var $timeout;
	var $setcookie;

	function cURL($cookies=TRUE,$cookie='cookies.txt',$compression='gzip',$proxy='')
	{
		$this->headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';
		$this->headers[] = 'Connection: Keep-Alive';
		$this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
		$this->user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)';
		$this->compression=$compression;
		$this->proxy=$proxy;
		$this->cookies=$cookies;
		if ($this->cookies == TRUE) $this->cookie($cookie);
	}

	function cookie($cookie_file)
	{
		if (file_exists($cookie_file))
		{
			$this->cookie_file=$cookie_file;
		}
		else
		{
			//@fopen($cookie_file,'w') or $this->cerror('The cookie file could not be opened. Make sure this directory has the correct permissions');
			@fopen($cookie_file,'w');
			$this->cookie_file=$cookie_file;
			@fclose($this->cookie_file);
		}
	}

	function head($url = '')
	{
		$settings[type] = "HEAD";
		return $this->get($url, $settings);
	}

	function code($url = '')
	{
		$settings[code] = true;
		return $this->get($url, $settings);
	}

	function get($url = '', $settings = array())
	{
		if (preg_match("[.tornevall.]", $url) || preg_match("[10.1.1.]", $url) || preg_match("[fnarg.org]", $url)) {unset($this->ip);}
		$process = curl_init($url);
		if ($this->proxy)
		{
			curl_setopt($process, CURLOPT_PROXY, $this->proxy);
			unset($this->ip);
		}
		if (!preg_match("/youtube/", $url))
		{
			if ($this->ip) {curl_setopt($process, CURLOPT_INTERFACE,$this->ip);}
		}
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		if ($this->timeout)
		{
			curl_setopt($process,CURLOPT_TIMEOUT,$this->timeout);
			curl_setopt($process, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		}
		curl_setopt($process, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($process, CURLOPT_VERBOSE,false);

		if (preg_match("/youtube/", $url))
		{
			foreach ($this->headers as $hid => $hdr)
			{
				if (preg_match("/content-type/i", $hdr))
				{
					unset($this->headers[$hid]);
				}
			}
		}

		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, $settings[header]);
		if ($settings[referer]) {curl_setopt($process, CURLOPT_REFERER, $settings[referer]);}
		if ($settings[type])
		{
			curl_setopt($process, CURLOPT_CUSTOMREQUEST, $settings[type]);
			if (strtolower($settings[type]) == "head")
			{
				curl_setopt($process, CURLOPT_HEADER, true);
				curl_setopt($process, CURLOPT_NOBODY, true);
			}
		}
		//curl_setopt($process, CURLOPT_HEADER, true);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		if ($this->setcookie) {curl_setopt($process, CURLOPT_COOKIE, $this->setcookie);}
		curl_setopt($process,CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		$return = curl_exec($process);
		if ($settings[code])
		{
			$return = curl_getinfo($process, CURLINFO_HTTP_CODE);
			if ($return == "") {$return = -1;}	// Fail!
		}
		curl_close($process);
		return $return;
	}
	function post($url,$data)
	{
		if (preg_match("[.tornevall.]", $url) || preg_match("[10.1.1.]", $url)) {unset($this->ip);}
		$process = curl_init($url);
		if ($this->proxy)
		{
			curl_setopt($process, CURLOPT_PROXY, $this->proxy);
			unset($this->ip);
		}
		if ($this->ip) {curl_setopt($process, CURLOPT_INTERFACE,$this->ip);}
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 1);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($settings[referer]) {curl_setopt($process, CURLOPT_REFERER, $settings[referer]);}
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		if ($this->timeout)
		{
			curl_setopt($process,CURLOPT_TIMEOUT,$this->timeout);
			curl_setopt($process, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		}
		if ($this->setcookie) {curl_setopt($process, CURLOPT_COOKIE, $this->setcookie);}
		curl_setopt($process, CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_POSTFIELDS, $data);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_POST, 1);
		$return = curl_exec($process);
		curl_close($process);
		return $return;
}

function cerror($error)
{
	echo "<center><div style='width:500px;border: 3px solid #FFEEFF; padding: 3px; background-color: #FFDDFF;font-family: verdana; font-size: 10px'><b>cURL Error</b><br>$error</div></center>";
	die;
}


}
?>