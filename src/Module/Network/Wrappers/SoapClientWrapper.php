<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\requestMethod;
use TorneLIB\Module\Network\Model\Wrapper;

/**
 * Class SoapClientWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class SoapClientWrapper implements Wrapper
{
    /**
     * @var WrapperConfig $CONFIG
     */
    private $CONFIG;

    public function __construct()
    {
        if (!class_exists('SoapClient')) {
            throw new ExceptionHandler('SOAP unavailable: SoapClient is missing.');
        }

        $this->CONFIG = new WrapperConfig();
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

    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL)
    {
    }
}
