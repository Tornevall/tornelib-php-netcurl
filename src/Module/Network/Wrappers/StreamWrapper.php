<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\requestMethod;
use TorneLIB\Module\Network\Model\Wrapper;

/**
 * Class StreamWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 * @version 6.1.0
 */
class StreamWrapper implements Wrapper
{
    /**
     * @var WrapperConfig $CONFIG
     */
    private $CONFIG;

    public function __construct()
    {
        throw new ExceptionHandler('Unhandled wrapper: Stream. Make sure the developer checks for existence before loading.');
    }

    /**
     * @param WrapperConfig $config
     * @return StreamWrapper
     * @version 6.1.0
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
     * @return StreamWrapper
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
     * @param $url
     * @param array $data
     * @param $method
     * @param int $dataType
     * @return $this|mixed
     * @version 6.1.0
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL)
    {
        if (!empty($url)) {
            $this->CONFIG->setRequestUrl($url);
        }
        if (is_array($data) && count($data)) {
            $this->CONFIG->setRequestData($data);
        }

        if ($this->CONFIG->getRequestMethod() !== $method) {
            $this->CONFIG->setRequestMethod($method);
        }

        if ($this->CONFIG->getRequestDataType() !== $dataType) {
            $this->CONFIG->setRequestDataType($dataType);
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
    }

    public function __get($name)
    {
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        // TODO: Implement getBody() method.
    }

    /**
     * @inheritDoc
     */
    public function getParsed()
    {
        // TODO: Implement getParsed() method.
    }
}
