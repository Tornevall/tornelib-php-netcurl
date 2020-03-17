<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\Version;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
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
     * @var
     */
    private $curlResponse;

    /**
     * @var int
     */
    private $curlHttpCode = 0;

    /**
     * @var array
     */
    private $curlResponseHeaders = [];

    /**
     * @var bool
     */
    private $isMultiCurl = false;

    /**
     * @var resource cURL multi handle
     */
    private $multiCurlHandle;

    /**
     * @var array
     */
    private $multiCurlHandleObjects = [];

    /**
     * @var
     */
    private $curlMultiResponse;

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
        $hasConstructorArguments = $this->getPriorCompatibilityArguments(func_get_args());
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
        if ($hasConstructorArguments) {
            $this->initCurlHandle();
        }
    }

    /**
     * Destructor for cleaning up resources.
     *
     * @since 6.1.0
     */
    public function __destruct()
    {
        if (is_resource($this->curlHandle)) {
            curl_close($this->curlHandle);
        }
        if ($this->isMultiCurl) {
            curl_multi_close($this->multiCurlHandle);
        }
    }

    /**
     * @param WrapperConfig $config
     */
    public function setConfig($config)
    {
        /** @var WrapperConfig CONFIG */
        $this->CONFIG = $config;
    }

    /**
     * Major initializer.
     *
     * @param $curlHandle
     * @param $url
     * @return $this
     */
    private function setupHandle($curlHandle, $url)
    {
        $this->setCurlStaticValues($curlHandle, $url);

        return $this;
    }

    /**
     * @param $curlHandle
     * @param $header
     * @return int
     */
    private function getCurlHeaderRow($curlHandle, $header)
    {
        $headSplit = explode(':', $header, 2);

        if (count($headSplit) < 2) {
            return strlen($header);
        }
        if (!$this->isMultiCurl) {
            $this->curlResponseHeaders[$headSplit[0]][] = trim($headSplit[1]);
        } else {
            $urlinfo = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
            if (!isset($this->curlResponseHeaders[$urlinfo])) {
                $this->curlResponseHeaders[$urlinfo] = [];
            }
            $this->curlResponseHeaders[$urlinfo][$headSplit[0]][] = trim($headSplit[1]);
        }

        return strlen($header);
    }

    /**
     * Values set here can not be changed via any other part of the wrapper.
     *
     * @param $curlHandle
     * @param $url
     * @return $this
     */
    private function setCurlStaticValues($curlHandle, $url)
    {
        $this->setOptionCurl($curlHandle, CURLOPT_URL, $url);
        $this->setOptionCurl($curlHandle, CURLOPT_HEADER, false);
        $this->setOptionCurl($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $this->setOptionCurl($curlHandle, CURLOPT_AUTOREFERER, true);
        $this->setOptionCurl($curlHandle, CURLINFO_HEADER_OUT, true);

        curl_setopt($curlHandle, CURLOPT_HEADERFUNCTION, [$this, 'getCurlHeaderRow']);

        return $this;
    }

    /**
     * Set curloptions.
     *
     * @param $curlHandle
     * @param $curlOpt
     * @param $value
     * @return bool
     */
    public function setOptionCurl($curlHandle, $curlOpt, $value)
    {
        return curl_setopt($curlHandle, $curlOpt, $value);
    }

    /**
     * @param $url
     * @throws ExceptionHandler
     */
    private function throwExceptionInvalidUrl($url)
    {
        if (!empty($url)) {
            throw new ExceptionHandler(
                sprintf(
                    '%s is not a valid URL.',
                    $url
                ),
                Constants::LIB_INVALID_URL
            );
        } else {
            throw new ExceptionHandler(
                'URL must not be empty.',
                Constants::LIB_EMPTY_URL
            );
        }
    }

    /**
     * Initialize simple or multi curl handles.
     *
     * @return CurlWrapper
     * @throws ExceptionHandler
     */
    private function initCurlHandle()
    {
        if (function_exists('curl_version')) {
            $this->curlVersion = curl_version();
        }

        if (is_string($this->CONFIG->getRequestUrl())) {
            if (!empty($this->CONFIG->getRequestUrl()) &&
                filter_var($this->CONFIG->getRequestUrl(), FILTER_VALIDATE_URL)
            ) {
                $this->curlHandle = curl_init();
                $this->setupHandle($this->curlHandle, $this->CONFIG->getRequestUrl());
            } else {
                $this->throwExceptionInvalidUrl($this->CONFIG->getRequestUrl());
            }
        } else {
            // Prepare for multiple curl requests.
            if (is_array($this->CONFIG->getRequestUrl()) && count($this->CONFIG->getRequestUrl())) {
                $this->isMultiCurl = true;
                $this->multiCurlHandle = curl_multi_init();
                foreach ($this->CONFIG->getRequestUrl() as $url) {
                    $this->multiCurlHandleObjects[$url] = curl_init();
                    $this->setupHandle(
                        $this->multiCurlHandleObjects[$url],
                        $url
                    );
                }
                $this->setMultiCurlHandles();
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getMultiCurlRequest()
    {
        $return = [];

        do {
            $status = curl_multi_exec($this->multiCurlHandle, $active);
            if ($active) {
                curl_multi_select($this->multiCurlHandle);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($this->multiCurlHandleObjects as $url => $curlHandleObject) {
            $return[$url] = curl_multi_getcontent($curlHandleObject);
            curl_multi_remove_handle($this->multiCurlHandle, $curlHandleObject);
        }
        //curl_multi_close($this->multiCurlHandle);

        return $return;
    }

    /**
     * The curl_exec part.
     * @return $this
     * @throws ExceptionHandler
     */
    public function getCurlRequest()
    {
        if (!$this->isMultiCurl && is_resource($this->getCurlHandle())) {
            $this->curlResponse = curl_exec($this->curlHandle);
            $this->curlHttpCode = curl_getinfo($this->curlHandle, CURLINFO_RESPONSE_CODE);
        } elseif (is_resource($this->multiCurlHandle)) {
            $this->curlMultiResponse = $this->getMultiCurlRequest();
        }

        return $this;
    }

    /**
     * Reverse compatibility with v6.0 - returns true if any of the settings here are touched.
     *
     * @param array $funcArgs
     * @return bool
     * @throws \Exception
     */
    private function getPriorCompatibilityArguments($funcArgs = [])
    {
        $return = false;

        foreach ($funcArgs as $funcIndex => $funcValue) {
            switch ($funcIndex) {
                case 0:
                    if (!empty($funcValue)) {
                        $this->CONFIG->setRequestUrl($funcValue);
                        $return = true;
                    }
                    break;
                case 1:
                    if (is_array($funcValue) && count($funcValue)) {
                        $this->CONFIG->setRequestData($funcValue);
                        $return = true;
                    }
                    break;
                case 2:
                    $this->CONFIG->setRequestMethod($funcValue);
                    $return = true;
                    break;
                case 3:
                    $this->CONFIG->setRequestFlags(is_array($funcValue) ? $funcValue : []);
                    $return = true;
                    break;
            }
        }

        return $return;
    }

    /**
     * @since 6.1.0
     */
    private function setMultiCurlHandles()
    {
        $reqUrlArray = (array)$this->CONFIG->getRequestUrl();
        foreach ($reqUrlArray as $url) {
            curl_multi_add_handle($this->multiCurlHandle, $this->multiCurlHandleObjects[$url]);
        }
    }

    /**
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function getConfig()
    {
        return $this->CONFIG;
    }

    /**
     * Returns simple curl handle only.
     *
     * @return resource
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getCurlHandle()
    {
        $return = null;

        if (is_resource($this->curlHandle)) {
            $return = $this->curlHandle;
        } else {
            if (is_resource($this->multiCurlHandle) && count($this->multiCurlHandleObjects)) {
                $return = $this->multiCurlHandle;
            } else {
                $return = $this->initCurlHandle()->getCurlHandle();
            }
        }

        return $return;
    }

    /**
     * @param string $specificKey
     * @param string $specificUrl
     * @return string
     * @throws ExceptionHandler
     * @since 6.0
     */
    public function getHeader($specificKey = '', $specificUrl = '')
    {
        $return = [];

        $headerRequest = is_array($this->curlResponseHeaders) ? $this->curlResponseHeaders : [];

        if ($this->isMultiCurl) {
            if (is_array($this->curlResponseHeaders) && count($this->curlResponseHeaders) === 1) {
                $headerRequest = array_pop($this->curlResponseHeaders);
            } else {
                if (empty($specificUrl)) {
                    throw new \TorneLIB\Exception\ExceptionHandler(
                        'You must specify the URL from which you want to retrieve headers.',
                        Constants::LIB_MULTI_HEADER
                    );
                }
                $headerRequest = isset($this->curlResponseHeaders[$specificUrl]) &&
                is_array($this->curlResponseHeaders[$specificUrl]) ? $this->curlResponseHeaders[$specificUrl] : [];
            }
        }

        if (is_array($headerRequest) && count($headerRequest)) {
            foreach ($headerRequest as $headKey => $headArray) {
                if (is_array($headArray) && count($headArray) === 1) {
                    if (!$specificKey) {
                        $return[] = sprintf("%s: %s", $headKey, array_pop($headArray));
                    } elseif (strtolower($specificKey) === strtolower($headKey)) {
                        $return[] = sprintf("%s", array_pop($headArray));
                    }
                }
            }
        }
        return implode("\n", $return);
    }

    /**
     * @return int
     * @since 6.0
     */
    public function getCode()
    {
        return $this->curlHttpCode;
    }

    /**
     * @param string $url
     * @return mixed
     * @since 6.0
     */
    public function getBody($url = '')
    {
        if (!$this->isMultiCurl) {
            $return = $this->curlResponse;
        } elseif (isset($this->curlMultiResponse[$url])) {
            $return = $this->curlMultiResponse[$url];
        } else {
            $return = $this->curlMultiResponse;
        }

        return $return;
    }

    /**
     * Get parsed response. No longer using IO.
     *
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.0
     */
    public function getParsed()
    {
        $return = $this->getBody();
        switch ($contentType = $this->getHeader('content-type')) {
            case (preg_match('/application\/json/i', $contentType) ? true : false):
                $return = json_decode($this->getBody());
                break;
            default:
                break;
        }

        return $return;
    }

    /**
     * @param string $url
     * @param array $data
     * @param int $method
     * @param int $dataType
     * @return $this
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function request($url = '', $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL)
    {
        if (!empty($url)) {
            $this->CONFIG->setRequestUrl($url);
        }
        if (is_array($data) && !count($data)) {
            $this->CONFIG->setRequestData($data);
        }

        if ($this->CONFIG->getRequestMethod() !== $method) {
            $this->CONFIG->setRequestMethod($method);
        }

        if ($this->CONFIG->getRequestDataType() !== $dataType) {
            $this->CONFIG->setRequestDataType($dataType);
        }

        $this->initCurlHandle();
        $this->getCurlRequest();
        $this->getCurlParse();

        return $this;
    }


    public function __call($name, $arguments)
    {
    }

    public function __get($name)
    {
    }
}
