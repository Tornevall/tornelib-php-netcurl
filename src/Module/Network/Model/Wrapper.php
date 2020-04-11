<?php

namespace TorneLIB\Module\Network\Model;

use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;

// Avoid conflicts and use what we have.
if (!defined('NETCURL_VERSION')) {
    define('NETCURL_VERSION', '6.1.0');
}

interface Wrapper
{
    /**
     * Wrapper constructor.
     * @since 6.1.0
     */
    public function __construct();

    /**
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function getConfig();

    /**
     * @param WrapperConfig $config
     * @return mixed
     * @since 6.1.0
     */
    public function setConfig($config);

    /**
     * @param $username
     * @param $password
     * @param authType $authType
     * @return array
     * @since 6.1.0
     */
    public function setAuthentication($username, $password, $authType);

    /**
     * @return array
     * @since 6.1.0
     */
    public function getAuthentication();

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getBody();

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getParsed();

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getCode();

    /**
     * @return string
     */
    public function getVersion();

    /**
     * @param $url
     * @param array $data
     * @param $method
     * @param int $dataType
     * @return mixed
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL);
}
