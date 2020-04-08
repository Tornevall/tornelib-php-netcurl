<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\requestMethod;
use TorneLIB\Module\Network\Model\Wrapper;

/**
 * Class SimpleWrapper Fetching tool in the simplest form. Using file_get_contents.
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class SimpleWrapper implements Wrapper
{
    /**
     * @var WrapperConfig $CONFIG
     */
    private $CONFIG;

    public function __construct()
    {
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
     * @inheritDoc
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL)
    {
    }

    public function __call($name, $arguments)
    {
    }

    public function __get($name)
    {
    }
}
