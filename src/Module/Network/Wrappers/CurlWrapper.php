<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\Version;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\Wrapper;
use TorneLIB\Utils\Security;

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

/**
 * Class CurlWrapper.
 *
 * Wrapper to make calls directly to the curl engine. This should not be used primarily if auto detection is the
 * preferred way to fetch data.
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class CurlWrapper implements Wrapper
{
    /**
     * @var WrapperConfig $CONFIG
     */
    private $CONFIG;

    /**
     * @var resource cURL simple handle
     */
    private $curlHandle;

    /**
     * @var resource cURL multi handle
     */
    private $multiCurlHandle;

    /**
     * @var string Data that probably should be added to the user-agent.
     */
    private $curlVersion;

    /**
     * CurlWrapper constructor.
     *
     * @throws ExceptionHandler
     */
    public function __construct()
    {
        $this->CONFIG = new WrapperConfig();
        $this->getPriorCompatibilityArguments(func_get_args());
        // Make sure there are available drivers before using the wrapper.
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            $secCheck = new Security();
            if ($secCheck->getDisabledFunction(['curl_init', 'curl_exec'])) {
                throw new ExceptionHandler(
                    'curl unavailable: It turns out that php.ini has the functions ' .
                    'curl_init and/or curl_exec disabled. Your should update php.ini and try again.'
                );
            }
            throw new ExceptionHandler('curl unavailable: curl_init and/or curl_exec not found.');
        }

        $this->initCurl();
    }

    /**
     * @param WrapperConfig $config
     */
    public function setConfig($config) {
        /** @var WrapperConfig CONFIG */
        $this->CONFIG = $config;
    }

    private function initCurl()
    {
        if (function_exists('curl_version')) {
            $this->curlVersion = curl_version();
        }
    }

    /**
     * Reverse compatibility with v6.0
     *
     * @param array $funcArgs
     * @throws \Exception
     */
    private function getPriorCompatibilityArguments($funcArgs = [])
    {
        foreach ($funcArgs as $funcIndex => $funcValue) {
            switch ($funcIndex) {
                case 0:
                    if (!empty($funcValue)) {
                        $this->CONFIG->setRequestUrl($funcValue);
                    }
                    break;
                case 1:
                    if (is_array($funcValue) && count($funcValue)) {
                        $this->CONFIG->setRequestVars($funcValue);
                    }
                    break;
                case 2:
                    $this->CONFIG->setRequestPostMethod($funcValue);
                    break;
                case 3:
                    $this->CONFIG->setRequestFlags(is_array($funcValue) ? $funcValue : []);
                    break;
            }
        }
    }

    /**
     * @return WrapperConfig
     */
    public function getConfig()
    {
        return $this->CONFIG;
    }

    public function __call($name, $arguments)
    {
    }

    public function __get($name)
    {
    }

    public function request()
    {
    }
}
