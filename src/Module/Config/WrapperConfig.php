<?php

namespace TorneLIB\Module\Config;

use TorneLIB\Config\Flag;
use TorneLIB\Flags;
use TorneLIB\Model\Type\postMethod;
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
    private $requestUrl = '';
    private $requestVars = [];
    private $requestPostMethod = postMethod::METHOD_GET;

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
            'CURLOPT_USERAGENT' => 'TorneLIB-PHPcURL',
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
    public function getRequestVars()
    {
        return $this->requestVars;
    }

    /**
     * @param array $requestVars
     * @since 6.1.0
     */
    public function setRequestVars($requestVars)
    {
        $this->requestVars = $requestVars;
    }

    /**
     * @param int $requestPostMethod
     * @since 6.1.0
     */
    public function setRequestPostMethod(int $requestPostMethod)
    {
        if (is_numeric($requestPostMethod)) {
            $this->requestPostMethod = $requestPostMethod;
        } else {
            $this->requestPostMethod = postMethod::METHOD_GET;
        }
    }

    /**
     * @return int
     * @since 6.1.0
     */
    public function getRequestPostMethod()
    {
        return $this->requestPostMethod;
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getRequestFlags()
    {
        return Flags::getAllFlags();
    }

    /**
     * @param array $requestFlags
     * @throws \Exception
     * @since 6.1.0
     */
    public function setRequestFlags(array $requestFlags)
    {
        Flags::setFlags($requestFlags);
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
}
