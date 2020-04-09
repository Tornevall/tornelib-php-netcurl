<?php

namespace TorneLIB\Module\Network\Wrappers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\requestMethod;
use TorneLIB\Module\Network\Model\Wrapper;

class GuzzleWrapper implements Wrapper
{
    public function __construct()
    {
        if (!class_exists('GuzzleHttp\Client') || !class_exists('GuzzleHttp\Handler\StreamHandler')) {
            throw new ExceptionHandler('zend unavailable: Zend\Http\Client not loaded');
        }
        //$cli = new Client('http://identifier.tornevall.net');
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        // TODO: Implement getConfig() method.
    }

    /**
     * @inheritDoc
     */
    public function setConfig($config)
    {
        // TODO: Implement setConfig() method.
    }

    /**
     * @inheritDoc
     */
    public function setAuthentication($username, $password, $authType)
    {
        // TODO: Implement setAuthentication() method.
    }

    /**
     * @inheritDoc
     */
    public function getAuthentication()
    {
        // TODO: Implement getAuthentication() method.
    }

    /**
     * @inheritDoc
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL)
    {
        // TODO: Implement request() method.
    }
}
