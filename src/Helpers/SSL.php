<?php

namespace TorneLIB\Helpers;

use TorneLIB\Flags;

class SSL
{
	public function __construct()
	{
		return $this;
	}

	public function getSslCapabilities() {

		if (!$this->setSslCapabilities()) {
			throw new \Exception('NETCURL Exception: SSL capabilities is missing.', 500);
		}

		return $this;
	}

	private function setSslCapabilities()
	{
		if (Flags::isFlag('NETCURL_NOSSL_TEST')) {
			return false;
		}

		$sslDriverError = [];

		if (!$this->getSslStreamWrapper()) {
			$sslDriverError[] = "SSL Failure: HTTPS wrapper can not be found";
		}
		if (!$this->getCurlSsl()) {
			$sslDriverError[] = 'SSL Failure: Protocol "https" not supported or disabled in libcurl';
		}

		return $this;
	}

	private function getSslStreamWrapper()
	{
		$return = false;

		$streamWrappers = @stream_get_wrappers();
		if (!is_array($streamWrappers)) {
			$streamWrappers = [];
		}
		if (in_array('https', array_map("strtolower", $streamWrappers))) {
			$return = true;
		}

		return $return;
	}

	private function getCurlSsl()
	{
		$return = false;
		if (function_exists('curl_version') && defined('CURL_VERSION_SSL')) {
			$curlVersionRequest = curl_version();

			if (isset($curlVersionRequest['features'])) {
				$return = ($curlVersionRequest['features'] & CURL_VERSION_SSL ? true : false);
			}
		}

		return $return;
	}
}
