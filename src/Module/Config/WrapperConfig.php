<?php

namespace TorneLIB\Module\Config;

use TorneLIB\Config\Flag;
use TorneLIB\Flags;
use TorneLIB\Helpers\Browsers;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\WrapperConstants;

/**
 * Class WrapperConfig
 *
 * Shared configurator. All wrapper services that needs shared configuration like credentials, SSL setup, etc, goes
 * here to set this up.
 *
 * @package Module\Config
 */
class WrapperConfig
{
    /**
     * @var string Requested URL.
     */
    private $requestUrl = '';

    /**
     * @var array Postdata.
     */
    private $requestData = [];

    /**
     * @var
     */
    private $requestDataContainer;

    /**
     * @var int Default method. Postdata will in the case of GET generate postdata in the link.
     */
    private $requestMethod = requestMethod::METHOD_GET;

    /**
     * @var int Datatype to post in (default = uses ?key=value for GET and &key=value in body for POST).
     */
    private $requestDataType = dataType::DEFAULT;

    /**
     * @var array Options that sets up each request engine. On curl, it is CURLOPT.
     */
    private $options = [];

    /**
     * WrapperConfig constructor.
     */
    public function __construct()
    {
        $this->setCurlDefaults();
    }

    /**
     * Preparing curl defaults in a way we like.
     *
     * @since 6.1.0
     */
    private function setCurlDefaults()
    {
        $this->getCurlConstants([
            'CURLOPT_CONNECTTIMEOUT' => 6,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_SSL_VERIFYPEER' => 1,
            'CURLOPT_SSL_VERIFYHOST' => 2,
            'CURLOPT_ENCODING' => 1,
            'CURLOPT_TIMEOUT' => 10,
            'CURLOPT_USERAGENT' => (new Browsers())->getBrowser(),
            'CURLOPT_POST' => true,
            'CURLOPT_SSLVERSION' => 4,
            'CURLOPT_FOLLOWLOCATION' => false,
            'CURLOPT_HTTPHEADER' => ['Accept-Language: en'],
        ]);
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
     * @since 6.1.0
     */
    private function getCurlConstants($curlOptConstant)
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
     * @since 6.1.0
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
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
                case dataType::DEFAULT:
                    $requestQuery = '';
                    if ($this->requestMethod === requestMethod::METHOD_GET) {
                        $requestQuery = '?';
                    }
                    $this->requestDataContainer = $requestQuery . http_build_query($this->requestData);
                default:
                    break;
            }
        }

        return $return;
    }

    /**
     * @param array $requestData
     * @since 6.1.0
     */
    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * @param int $requestMethod
     * @since 6.1.0
     */
    public function setRequestMethod($requestMethod)
    {
        if (is_numeric($requestMethod)) {
            $this->requestMethod = $requestMethod;
        } else {
            $this->requestMethod = requestMethod::METHOD_GET;
        }
    }

    /**
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
     * @param array $options
     * @since 6.1.0
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param int $requestDataType
     */
    public function setRequestDataType(int $requestDataType)
    {
        $this->requestDataType = $requestDataType;
    }

    /**
     * @return int
     */
    public function getRequestDataType()
    {
        return $this->requestDataType;
    }
}
