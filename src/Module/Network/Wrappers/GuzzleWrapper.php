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
use TorneLIB\Utils\Generic;

/**
 * Class GuzzleWrapper Prepared wrapper for Guzzle, if preferred over regular drivers. Probably quite unnecessary
 * since Guzzle uses curl and/or streams.
 *
 * @package TorneLIB\Module\Network\Wrappers
 * @version 6.1.0
 * @since 6.0 Wrapper was included in 6.0 but with another look.
 */
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
    public function getVersion()
    {
        $return = $this->version;

        if (empty($return)) {
            $return = (new Generic())->getVersionByClassDoc(__CLASS__);
        }

        return $return;
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

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        // TODO: Implement getCode() method.
    }
}
