<?php

namespace TorneLIB\Module\Config;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Flags;
use TorneLIB\Helpers\Browsers;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Model\Type\authSource;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;

/**
 * Class WrapperConfig
 *
 * Shared configurator. All wrapper services that needs shared configuration like credentials, SSL setup, etc, goes
 * here to set this up.
 *
 * @package Module\Config
 * @version 6.1.0
 */
class WrapperConfig
{
    /**
     * @var string Requested URL.
     * @since 6.1.0
     */
    private $requestUrl = '';

    /**
     * @var array Postdata.
     * @since 6.1.0
     */
    private $requestData = [];

    /**
     * @var
     * @since 6.1.0
     */
    private $requestDataContainer;

    /**
     * @var int Default method. Postdata will in the case of GET generate postdata in the link.
     * @since 6.1.0
     */
    private $requestMethod = requestMethod::METHOD_GET;

    /**
     * @since 6.1.0
     * @var int Datatype to post in (default = uses ?key=value for GET and &key=value in body for POST).
     */
    private $requestDataType = dataType::NORMAL;

    /**
     * @var array Options that sets up each request engine. On curl, it is CURLOPT.
     * @since 6.1.0
     */
    private $options = [];

    /**
     * @var array Initial SoapOptions
     *
     *   WSDL_CACHE_NONE = 0
     *   WSDL_CACHE_DISK = 1
     *   WSDL_CACHE_MEMORY = 2
     *   WSDL_CACHE_BOTH = 3
     *
     * @since 6.1.0
     */
    private $streamOptions = [
        'exceptions' => true,
        'trace' => true,
        'cache_wsdl' => 0,
        'stream_context' => null,
    ];

    /**
     * @var array Authentication data.
     * @since 6.1.0
     */
    private $authData = ['username' => '', 'password' => '', 'type' => 1];

    /**
     * @var array Throwable HTTP codes.
     * @since 6.1.0
     */
    private $throwableHttpCodes;

    /**
     * @var array
     * @since 6.1.0
     */
    private $configData = [];

    private $SSL;

    /**
     * WrapperConfig constructor.
     * @since 6.1.0
     */
    public function __construct()
    {
        $this->SSL = new WrapperSSL();
        $this->setThrowableHttpCodes();
        $this->setCurlDefaults();

        return $this;
    }

    /**
     * Set up a list of which HTTP error codes that should be throwable (default: >= 400, <= 599).
     *
     * @param int $throwableMin Minimum value to throw on (Used with >=)
     * @param int $throwableMax Maxmimum last value to throw on (Used with <)
     * @return WrapperConfig
     * @since 6.0.6 Since netcurl.
     */
    public function setThrowableHttpCodes($throwableMin = 400, $throwableMax = 599)
    {
        $throwableMin = intval($throwableMin) > 0 ? $throwableMin : 400;
        $throwableMax = intval($throwableMax) > 0 ? $throwableMax : 599;
        $this->throwableHttpCodes[] = [$throwableMin, $throwableMax];

        return $this;
    }

    /**
     * Throw on any code that matches the store throwableHttpCode (use with setThrowableHttpCodes())
     *
     * @param string $httpMessageString
     * @param string $httpCode
     * @param null $extendException
     * @throws ExceptionHandler
     * @since 6.0.6
     */
    public function getHttpException(
        $httpMessageString = '',
        $httpCode = '',
        $extendException = null,
        $forceException = false
    ) {
        if (!is_array($this->throwableHttpCodes)) {
            $this->throwableHttpCodes = [];
        }
        foreach ($this->throwableHttpCodes as $codeListArray => $codeArray) {
            if (
                (
                    isset($codeArray[1]) &&
                    $httpCode >= intval($codeArray[0]) &&
                    $httpCode <= intval($codeArray[1])
                ) || $forceException
            ) {
                throw new ExceptionHandler(
                    sprintf(
                        'Error %d returned from server: "%s".',
                        $httpCode,
                        $httpMessageString
                    ),
                    $httpCode,
                    null,
                    null,
                    null,
                    $extendException
                );
            }
        }
    }

    /**
     * Return the list of throwable http error codes (if set)
     *
     * @return array
     * @since 6.0.6 Since netcurl.
     */
    public function getThrowableHttpCodes()
    {
        return $this->throwableHttpCodes;
    }

    /**
     * Preparing curl defaults in a way we like.
     * @return $this
     * @since 6.1.0
     */
    private function setCurlDefaults()
    {
        $this->setTimeout(6);

        $this->setCurlConstants([
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_SSL_VERIFYPEER' => 1,
            'CURLOPT_SSL_VERIFYHOST' => 2,
            'CURLOPT_ENCODING' => 1,
            'CURLOPT_USERAGENT' => (new Browsers())->getBrowser(),
            'CURLOPT_SSLVERSION' => CURL_SSLVERSION_DEFAULT,
            'CURLOPT_FOLLOWLOCATION' => false,
            'CURLOPT_HTTPHEADER' => ['Accept-Language: en'],
        ]);

        return $this;
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getCurlDefaults()
    {
        return $this->options;
    }

    /**
     * While setting up curloptions, make sure no warnings leak from the setup if constants are missing in the system.
     * If the constants are missing, this probably means that curl is not properly installed. We've seen this in prior
     * versions of netcurl where missing constants either screams about missing constants or makes sites bail out.
     *
     * @param mixed $curlOptConstant
     * @return WrapperConfig
     * @since 6.1.0
     */
    private function setCurlConstants($curlOptConstant)
    {
        if (is_array($curlOptConstant)) {
            foreach ($curlOptConstant as $curlOptKey => $curlOptValue) {
                $constantValue = @constant($curlOptKey);
                if (empty($constantValue)) {
                    // Fall back to internally stored constants if curl is not there.
                    $constantValue = @constant('TorneLIB\Module\Config\WrapperCurlOpt::NETCURL_' . $curlOptKey);
                }
                $this->options[$constantValue] = $curlOptValue;
            }
        }

        return $this;
    }

    /**
     * @return string
     * @since 6.1.0
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * @param string $requestUrl
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;

        return $this;
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getRequestData()
    {
        $return = $this->requestData;

        // Return as is on string.
        if (!is_string($return)) {
            switch ($this->requestDataType) {
                case dataType::JSON:
                    $this->requestDataContainer = $this->getJsonData($return);
                    $return = $this->requestDataContainer;
                    break;
                case dataType::NORMAL:
                    $requestQuery = '';
                    if ($this->requestMethod === requestMethod::METHOD_GET && !empty($this->requestData)) {
                        // Add POST data to request if anything else follows.
                        $requestQuery = '&';
                    }
                    $this->requestDataContainer = $this->requestData;
                    if (is_array($this->requestData) || is_object($this->requestData)) {
                        $httpQuery = http_build_query($this->requestData);
                        if (!empty($httpQuery)) {
                            $this->requestDataContainer = $requestQuery . $httpQuery;
                        }
                    }
                    $return = $this->requestDataContainer;
                    break;
                default:
                    break;
            }
        }

        return $return;
    }

    /**
     * @param $transformData
     * @return string
     * @since 6.1.0
     */
    private function getJsonData($transformData)
    {
        $return = $transformData;

        if (is_string($transformData)) {
            $stringTest = json_decode($transformData);
            if (is_object($stringTest) || is_array($stringTest)) {
                $return = $transformData;
            }
        } else {
            $return = json_encode($transformData);
        }

        return (string)$return;
    }

    /**
     * User input variables.
     *
     * @param array $requestData
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;

        return $this;
    }

    /**
     * POST, GET, DELETE, etc
     *
     * @param int $requestMethod
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function setRequestMethod($requestMethod)
    {
        if (is_numeric($requestMethod)) {
            $this->requestMethod = $requestMethod;
        } else {
            $this->requestMethod = requestMethod::METHOD_GET;
        }

        return $this;
    }

    /**
     * POST, GET, DELETE, etc
     *
     * @return int
     * @since 6.1.0
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getRequestFlags()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Flags::_getAllFlags();
    }

    /**
     * @param array $requestFlags
     * @throws \Exception
     * @since 6.1.0
     */
    public function setRequestFlags(array $requestFlags)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        Flags::_setFlags($requestFlags);
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $streamOptions
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function setStreamOptions(array $streamOptions)
    {
        $this->streamOptions = $streamOptions;

        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @param null $subKey
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function setStreamContext($key, $value, $subKey = null)
    {
        $currentStreamContext = $this->getStreamContext();

        if (is_resource($currentStreamContext)) {
            $currentStreamContext = stream_context_get_options($currentStreamContext);
        } else {
            $currentStreamContext = [];
        }

        if (is_array($currentStreamContext)) {
            if (is_null($subKey)) {
                $currentStreamContext[$key] = $value;
            } else {
                if (isset($currentStreamContext[$subKey])) {
                    $currentStreamContext[$subKey] = [];
                }
                $currentStreamContext[$subKey][$key] = $value;
            }
        }

        $this->streamOptions['stream_context'] = stream_context_create($currentStreamContext);

        return $this;
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getStreamContext()
    {
        return $this->streamOptions['stream_context'];
    }

    /**
     * Get current soapoptions.
     *
     * @return array
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getStreamOptions()
    {
        $this->setRenderedStreamOptions();
        $this->setStreamContext('ssl', $this->SSL->getContext());

        return $this->streamOptions;
    }

    /**
     * Prepare streamoption array.
     *
     * @return $this
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function setRenderedStreamOptions()
    {
        $this->setRenderedUserAgent();

        return $this;
    }

    /**
     * Handle user-agent in streams.
     *
     * @return $this
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function setRenderedUserAgent()
    {
        $this->setStreamContext('user_agent', $this->getUserAgent(), 'http');

        return $this;
    }

    /**
     * @param array $options
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Find out if there is a predefined constant for CURL-options and if the curl library actually exists.
     * If the constants don't exist, fall back to NETCURL constants so that we can still fetch the setup.
     *
     * @param $key
     * @return mixed|null
     * @since 6.1.0
     */
    private function getOptionCurl($key)
    {
        $return = null;

        if (preg_match('/CURL/', $key)) {
            $constantValue = @constant('TorneLIB\Module\Config\WrapperCurlOpt::NETCURL_' . $key);
            if (!empty($constantValue)) {
                $return = $constantValue;
            }
        }

        return $return;
    }

    /**
     * @param $key
     * @param $value
     * @param bool $isSoap
     * @return $this
     * @since 6.1.0
     */
    public function setOption($key, $value, $isSoap = false)
    {
        $preKey = $this->getOptionCurl($key);
        if (!empty($preKey)) {
            $key = $preKey;
        }

        if (!$isSoap) {
            $this->options[$key] = $value;
        } else {
            $this->streamOptions[$key] = $value;
        }

        return $this;
    }

    /**
     * @param $key
     * @param bool $isSoap
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getOption($key, $isSoap = false)
    {
        $preKey = $this->getOptionCurl($key);
        if (!empty($preKey)) {
            $key = $preKey;
        }

        if (!$isSoap) {
            if (isset($this->options[$key])) {
                return $this->options[$key];
            }
        } else {
            if (isset($this->streamOptions[$key])) {
                return $this->streamOptions[$key];
            }
        }

        throw new ExceptionHandler(
            sprintf('%s: Option "%s" not set.', __CLASS__, $key),
            404
        );
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @since 6.1.0
     */
    public function setStreamOption($key, $value)
    {
        return $this->setOption($key, $value, true);
    }

    /**
     * @param $key
     * @return mixed
     * @throws ExceptionHandler
     */
    public function getStreamOption($key)
    {
        return $this->getOption($key, true);
    }

    /**
     * @param $key
     * @return $this
     * @since 6.1.0
     */
    public function deleteOption($key)
    {
        $preKey = $this->getOptionCurl($key);
        if (!empty($preKey)) {
            $key = $preKey;
        }

        if (isset($this->options[$key])) {
            unset($this->options[$key]);
        }

        return $this;
    }

    /**
     * Replace an option with another.
     *
     * @param $key
     * @param $value
     * @param $replace
     * @return $this
     * @since 6.1.0
     */
    public function replaceOption($key, $value, $replace)
    {
        $this->deleteOption($replace);
        $this->setOption($key, $value);
        return $this;
    }

    /**
     * Datatype of request (json, etc).
     * @param int $requestDataType
     * @since 6.1.0
     */
    public function setRequestDataType(int $requestDataType)
    {
        $this->requestDataType = $requestDataType;
    }

    /**
     * Datatype of request (json, etc).
     * @return int
     * @since 6.1.0
     */
    public function getRequestDataType()
    {
        return $this->requestDataType;
    }

    /**
     * Set authdata.
     *
     * @param $username
     * @param $password
     * @param int $authType
     * @param int $authSource
     * @since 6.1.0
     */
    public function setAuthentication(
        $username,
        $password,
        $authType = authType::BASIC,
        $authSource = authSource::NORMAL
    ) {
        switch ($authSource) {
            case authSource::SOAP:
                $this->authData['login'] = $username;
                $this->authData['password'] = $password;
                $this->setStreamOption('login', $this->authData['login']);
                $this->setStreamOption('password', $this->authData['password']);
                break;
            default:
                $this->authData['username'] = $username;
                $this->authData['password'] = $password;
                $this->authData['type'] = $authType;
                break;
        }
    }

    /**
     * Get authdata.
     *
     * @return array
     * @since 6.1.0
     */
    public function getAuthentication()
    {
        return (array)$this->authData;
    }

    /**
     * @param int $timeout Defaults to the default connect timeout in curl (300).
     * @param bool $useMillisec Set timeouts in milliseconds instead of seconds.
     * @return $this
     * @link https://curl.haxx.se/libcurl/c/curl_easy_setopt.html
     * @since 6.1.0
     */
    private function setTimeout($timeout = 300, $useMillisec = false)
    {
        /**
         * CURLOPT_TIMEOUT (Entire request) Everything has to be established and get finished on this time limit.
         * CURLOPT_CONNECTTIMEOUT (Connection phase) We set this to half the time of the entire timeout request.
         * CURLOPT_ACCEPTTIMEOUT (Waiting for connect back to be accepted). Defined in MS only.
         *
         * @link https://curl.haxx.se/libcurl/c/curl_easy_setopt.html
         */

        // Using internal WrapperCurlOpts if curl is not a present driver. Otherwise, this
        // setup may be a showstopper that no other driver can use.
        if (!$useMillisec) {
            $this->replaceOption(
                WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT,
                ceil($timeout / 2),
                WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT_MS
            );
            $this->replaceOption(
                WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT,
                ceil($timeout),
                WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT_MS
            );
        } else {
            $this->replaceOption(
                WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT_MS,
                ceil($timeout / 2),
                WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT
            );
            $this->replaceOption(
                WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT_MS,
                ceil($timeout),
                WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT
            );
        }

        return $this;
    }

    /**
     * @param $userAgentString
     * @param int $source
     * @return $this
     * @since 6.1.0
     */
    public function setUserAgent($userAgentString)
    {
        $this->setOption(WrapperCurlOpt::NETCURL_CURLOPT_USERAGENT, $userAgentString);

        return $this;
    }

    /**
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getUserAgent()
    {
        return $this->getOption(WrapperCurlOpt::NETCURL_CURLOPT_USERAGENT);
    }

    /**
     * Returns internal information about the configured timeouts.
     *
     * @return array
     * @since 6.1.0
     */
    private function getTimeout()
    {
        $timeoutIsMillisec = false;
        if (isset($this->options[WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT])) {
            $cTimeout = $this->options[WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT]; // connectTimeout
        }
        if (isset($this->options[WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT_MS])) {
            $cTimeout = $this->options[WrapperCurlOpt::NETCURL_CURLOPT_CONNECTTIMEOUT_MS]; // connectTimeout
            $timeoutIsMillisec = true;
        }

        if (isset($this->options[WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT])) {
            $eTimeout = $this->options[WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT];  // entireTimeout
        }
        if (isset($this->options[WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT_MS])) {
            $eTimeout = $this->options[WrapperCurlOpt::NETCURL_CURLOPT_TIMEOUT_MS];  // entireTimeout
            $timeoutIsMillisec = true;
        }

        return [
            'CONNECT' => $cTimeout,
            'REQUEST' => $eTimeout,
            'MILLISEC' => $timeoutIsMillisec,
        ];
    }

    /**
     * Quickset WSDL cache.
     *
     *   WSDL_CACHE_NONE = 0
     *   WSDL_CACHE_DISK = 1
     *   WSDL_CACHE_MEMORY = 2
     *   WSDL_CACHE_BOTH = 3
     *
     * @param int $cacheSet
     * @return WrapperConfig
     */
    private function setWsdlCache($cacheSet = 0)
    {
        $this->streamOptions['cache_wsdl'] = $cacheSet;

        return $this;
    }

    /**
     * Internal configset magics.
     *
     * @param $name
     * @param $arguments
     * @return $this|mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        $method = substr($name, 0, 3);
        $methodContent = (new Strings())->getCamelCase(substr($name, 3));

        switch (strtolower($method)) {
            case 'get':
                if (method_exists($this, sprintf('get%s', ucfirst($methodContent)))) {
                    return call_user_func_array(
                        [
                            $this,
                            sprintf(
                                'get%s',
                                ucfirst($methodContent)
                            ),
                        ],
                        []
                    );
                }

                if (isset($this->configData[$methodContent])) {
                    return $this->configData[$methodContent];
                }

                throw new ExceptionHandler('Variable not set.', Constants::LIB_CONFIGWRAPPER_VAR_NOT_SET);
                break;
            case 'set':
                if (method_exists($this, sprintf('set%s', ucfirst($methodContent)))) {
                    call_user_func_array(
                        [
                            $this,
                            sprintf('set%s', ucfirst($methodContent)),
                        ],
                        $arguments
                    );
                }

                $this->configData[$methodContent] = array_pop($arguments);
                break;
            default:
                break;
        }

        return $this;
    }
}
