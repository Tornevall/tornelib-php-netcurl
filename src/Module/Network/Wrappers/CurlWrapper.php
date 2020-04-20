<?php

namespace TorneLIB\Module\Network\Wrappers;

use Exception;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\Version;
use TorneLIB\IO\Data\Content;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\Wrapper;
use TorneLIB\Utils\Generic;
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
 * @version 6.1.0
 */
class CurlWrapper implements Wrapper
{
    private $version = '6.1.0';

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
     * @var array
     */
    private $customPreHeaders = [];

    /**
     * @var array
     */
    private $customHeaders = [];

    /**
     * @var string Custom content type.
     */
    private $contentType = '';

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
     * @inheritDoc
     */
    public function getVersion()
    {
        $return = $this->version;

        if (empty($return)) {
            $return = (new Generic())->getVersionByClassDoc(__CLASS__);
        }

        return $return;
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
     * Major initializer.
     *
     * @param $curlHandle
     * @param $url
     * @return $this
     */
    private function setupHandle($curlHandle, $url)
    {
        $this->setCurlAuthentication($curlHandle);
        $this->setCurlDynamicValues($curlHandle);
        $this->setCurlSslValues($curlHandle);
        $this->setCurlStaticValues($curlHandle, $url);
        $this->setCurlPostData($curlHandle);
        $this->setCurlRequestMethod($curlHandle);
        $this->setCurlCustomHeaders($curlHandle);
        $this->setOptionCurl($curlHandle, CURLOPT_URL, $url);

        return $this;
    }

    /**
     * @param $curlHandle
     * @return CurlWrapper
     */
    private function setCurlRequestMethod($curlHandle)
    {
        switch ($this->CONFIG->getRequestMethod()) {
            case requestMethod::METHOD_POST:
                $this->setOptionCurl($curlHandle, CURLOPT_CUSTOMREQUEST, 'POST');
                break;
            case requestMethod::METHOD_DELETE:
                $this->setOptionCurl($curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case requestMethod::METHOD_HEAD:
                $this->setOptionCurl($curlHandle, CURLOPT_CUSTOMREQUEST, 'HEAD');
                break;
            case requestMethod::METHOD_PUT:
                $this->setOptionCurl($curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case requestMethod::METHOD_REQUEST:
                $this->setOptionCurl($curlHandle, CURLOPT_CUSTOMREQUEST, 'REQUEST');
                break;
            default:
                // Making sure we send data in proper formatting if there is bad user configuration.
                // Bad configuration is when both GET+POST data parameters are sent as a GET when the
                // correct set up in that case is a POST.
                $this->setOptionCurl($curlHandle, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
        }

        return $this;
    }

    /**
     * @param $curlHandle
     * @return CurlWrapper
     * @since 6.1.0
     */
    private function setCurlPostData($curlHandle)
    {
        $requestData = $this->CONFIG->getRequestData();

        switch ($this->CONFIG->getRequestDataType()) {
            case dataType::XML:
                $this->setCurlPostXmlHeader($curlHandle, $requestData);
                break;
            case dataType::JSON:
                $this->setCurlPostJsonHeader($curlHandle, $requestData);
                break;
            default:
                if ($this->CONFIG->getRequestMethod() === requestMethod::METHOD_POST) {
                    $this->setOptionCurl($curlHandle, CURLOPT_POST, true);
                }
                $this->setOptionCurl($curlHandle, CURLOPT_POSTFIELDS, $requestData);
                break;
        }

        return $this;
    }

    /**
     * @param $curlHandle
     * @param $requestData
     * @return $this
     */
    private function setCurlPostJsonHeader($curlHandle, $requestData)
    {
        $jsonContentType = 'application/json; charset=utf-8';

        $testContentType = $this->getContentType();
        if (preg_match("/json/i", $testContentType)) {
            $jsonContentType = $testContentType;
        }

        $this->customPreHeaders['Content-Type'] = $jsonContentType;
        $this->customPreHeaders['Content-Length'] = strlen($requestData);
        $this->setOptionCurl($curlHandle, CURLOPT_POSTFIELDS, $requestData);

        return $this;
    }

    /**
     * @param $curlHandle
     * @param $requestData
     * @return $this
     * @throws ExceptionHandler
     * @since 6.1.0
     * @todo Convert arrayed data to XML.
     */
    public function setCurlPostXmlHeader($curlHandle, $requestData)
    {
        if (is_array($requestData)) {
            throw new ExceptionHandler(
                'Convert arrayed data to XML error - no parsed present!',
                Constants::LIB_UNHANDLED
            );
        }

        $this->customPreHeaders['Content-Type'] = 'Content-Type: text/xml; charset=utf-8';
        $this->customPreHeaders['Content-Length'] = strlen($requestData);
        $this->setOptionCurl($curlHandle, CURLOPT_POSTFIELDS, $requestData);

        return $this;
    }

    /**
     * @param string $setContentTypeString
     *
     * @since 6.0.17
     */
    public function setContentType($setContentTypeString = 'application/json; charset=utf-8')
    {
        $this->contentType = $setContentTypeString;
    }

    /**
     * @return string
     * @since 6.0.17
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param $curlHandle
     * @since 6.1.0
     */
    private function setCurlCustomHeaders($curlHandle)
    {
        $this->setProperCustomerHeader();
        $this->setupHeaders($curlHandle);
    }

    /**
     * @param string $key
     * @param string $value
     * @since 6.0
     */
    public function setCurlHeader($key = '', $value = '')
    {
        if (!empty($key)) {
            if (!is_array($key)) {
                $this->customPreHeaders[$key] = $value;
            } else {
                foreach ($key as $arrayKey => $arrayValue) {
                    $this->customPreHeaders[$arrayKey] = $arrayValue;
                }
            }
        }
    }

    /**
     * Fix problematic header data by converting them to proper outputs.
     *
     * @since 6.1.0
     */
    private function setProperCustomerHeader()
    {
        foreach ($this->customPreHeaders as $headerKey => $headerValue) {
            $testHead = explode(":", $headerValue, 2);
            if (isset($testHead[1])) {
                $this->customHeaders[] = $headerValue;
            } elseif (!is_numeric($headerKey)) {
                $this->customHeaders[] = $headerKey . ": " . $headerValue;
            }
            unset($this->customPreHeaders[$headerKey]);
        }
    }

    /**
     * @param $curlHandle
     * @return $this
     * @since 6.1.0
     */
    private function setupHeaders($curlHandle)
    {
        if (count($this->customHeaders)) {
            $this->setOptionCurl($curlHandle, CURLOPT_HTTPHEADER, $this->customHeaders);
        }

        return $this;
    }

    /**
     * @param $curlHandle
     * @return CurlWrapper
     * @since 6.1.0
     */
    private function setCurlDynamicValues($curlHandle)
    {
        foreach ($this->CONFIG->getOptions() as $curlKey => $curlValue) {
            $this->setOptionCurl($curlHandle, $curlKey, $curlValue);
        }

        return $this;
    }

    /**
     * @param $curlHandle
     * @return CurlWrapper
     * @since 6.1.0
     */
    private function setCurlSslValues($curlHandle)
    {
        if (version_compare(PHP_VERSION, '5.4.11', ">=")) {
            $this->setOptionCurl($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            $this->setOptionCurl($curlHandle, CURLOPT_SSL_VERIFYHOST, 1);
        }
        $this->setOptionCurl($curlHandle, CURLOPT_SSL_VERIFYPEER, 1);

        return $this;
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
        $this->setOptionCurl($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $this->setOptionCurl($curlHandle, CURLOPT_HEADER, false);
        $this->setOptionCurl($curlHandle, CURLOPT_AUTOREFERER, true);
        $this->setOptionCurl($curlHandle, CURLINFO_HEADER_OUT, true);
        $this->setOptionCurl($curlHandle, CURLOPT_HEADERFUNCTION, [$this, 'getCurlHeaderRow']);

        return $this;
    }

    /**
     * @param $curlHandle
     * @return CurlWrapper
     */
    private function setCurlAuthentication($curlHandle)
    {
        $authData = $this->getAuthentication();
        if (!empty($authData['password'])) {
            $this->setOptionCurl($curlHandle, CURLOPT_HTTPAUTH, $authData['type']);
            $this->setOptionCurl(
                $curlHandle, CURLOPT_USERPWD,
                sprintf('%s:%s', $authData['username'], $authData['password'])
            );
        }

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
        $spacedSplit = explode(' ', $header, 2);

        if (count($headSplit) < 2) {
            if (count($spacedSplit) > 1) {
                $this->curlResponseHeaders[$spacedSplit[0]][] = trim($spacedSplit[1]);
            }
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
     * Set curloptions.
     *
     * @param $curlHandle
     * @param $curlOpt
     * @param $value
     * @return bool
     */
    public function setOptionCurl($curlHandle, $curlOpt, $value)
    {
        $this->CONFIG->setOption($curlOpt, $value);
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
            $requestUrl = $this->CONFIG->getRequestUrl();
            if (!empty($requestUrl) &&
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
     * @param $curlHandle
     * @param $httpCode
     * @param Exception $previousException
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getCurlException($curlHandle, $httpCode, $previousException = null)
    {
        $errorString = curl_error($curlHandle);
        $errorCode = curl_errno($curlHandle);
        if ($errorCode) {
            throw new ExceptionHandler(
                sprintf(
                    'curl error (%s): %s',
                    $errorCode,
                    $errorString
                ),
                $errorCode
            );
        }

        $httpHead = $this->getHeader('http');
        if (empty($errorString) && !empty($httpHead)) {
            $errorString = $httpHead;
        }
        $this->CONFIG->getHttpException($errorString, $httpCode, null, $this);
    }

    /**
     * The curl_exec part.
     * @return $this
     * @throws ExceptionHandler
     */
    public function getCurlRequest()
    {
        // Reset responseheader on each request.
        $this->curlResponseHeaders = [];
        $this->initCurlHandle();

        if (!$this->isMultiCurl && is_resource($this->getCurlHandle())) {
            $this->curlResponse = curl_exec($this->curlHandle);
            // Friendly anti-backfire support.
            $this->curlHttpCode = curl_getinfo(
                $this->curlHandle,
                defined('CURLINFO_RESPONSE_CODE') ? CURLINFO_RESPONSE_CODE : 2097154
            );
            $this->getCurlException($this->curlHandle, $this->curlHttpCode);
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
     * @throws Exception
     */
    private function getPriorCompatibilityArguments($funcArgs = [])
    {
        return $this->CONFIG->getCompatibilityArguments($funcArgs);
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
     * @param WrapperConfig $config
     * @return CurlWrapper
     */
    public function setConfig($config)
    {
        /** @var WrapperConfig CONFIG */
        $this->CONFIG = $config;

        return $this;
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
     * @param $username
     * @param $password
     * @param int $authType
     * @return CurlWrapper
     * @since 6.1.0
     */
    public function setAuthentication($username, $password, $authType = authType::BASIC)
    {
        $this->CONFIG->setAuthentication($username, $password, $authType);

        return $this;
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getAuthentication()
    {
        return $this->CONFIG->getAuthentication();
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
                // Something has pushed in duplicates of a header row, so lets pop one.
                if (count($headArray) > 1) {
                    $headArray = array_pop($headArray);
                }
                if (is_array($headArray) && count($headArray) === 1) {
                    if (!$specificKey) {
                        $return[] = sprintf("%s: %s", $headKey, array_pop($headArray));
                    } elseif (strtolower($specificKey) === strtolower($headKey)) {
                        $return[] = sprintf("%s", array_pop($headArray));
                    } elseif (strtolower($specificKey) === 'http') {
                        if (preg_match('/^http/i', $headKey)) {
                            $return[] = sprintf("%s", array_pop($headArray));
                        }
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

        // In v6.0, netcurl was much for guessing games. For 6.1, we trust content types.
        switch (($contentType = $this->getHeader('content-type'))) {
            case (!empty($contentType) && preg_match('/\/xml/i', $contentType) ? true : false):
                $return = (new Content())->getFromXml($return);
                break;
            case (preg_match('/\/json/i', $contentType) ? true : false):
                $return = json_decode($return);
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
        $this->CONFIG->request($url, $data, $method, $dataType);

        $this->getCurlRequest();
        $this->getCurlParse();

        return $this;
    }

    public function __call($name, $arguments)
    {
        return $this;
    }
}
