<?php

namespace TorneLIB\Helpers;

use TorneLIB\Flags;
use TorneLIB\Utils\Security;

/**
 * Class SSL Imports and facelifts from MODULE_SSH v6.0
 * @package TorneLIB\Helpers
 * @version 6.1.0
 */
class SSL
{
    private $capable;

    /**
     * SSL constructor.
     */
    public function __construct()
    {
        try {
            $this->capable = $this->setSslCapabilities();
        } catch (\Exception $e) {
            $this->capable = false;
        }
        return $this;
    }

    /**
     * Checks if system has SSL capabilities.
     *
     * Replaces getCurlSslAvailable from v6.0 where everything is checked in the same method.
     *
     * @return $this
     * @throws \Exception
     * @since 6.1.0
     */
    public function getSslCapabilities()
    {
        if (!($return = $this->capable)) {
            throw new \Exception('NETCURL Exception: SSL capabilities is missing.', 500);
        }

        return $return;
    }

    /**
     * @return bool
     * @since 6.1.0
     */
    private function setSslCapabilities()
    {
        $return = false;

        if (Flags::isFlag('NETCURL_NOSSL_TEST')) {
            return $return;
        }

        $sslDriverError = [];

        if (!$this->getSslStreamWrapper()) {
            $sslDriverError[] = "SSL Failure: HTTPS wrapper can not be found";
        }
        if (!$this->getCurlSsl()) {
            $sslDriverError[] = 'SSL Failure: Protocol "https" not supported or disabled in libcurl';
        }

        if (!count($sslDriverError)) {
            $return = true;
        }

        return $return;
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
