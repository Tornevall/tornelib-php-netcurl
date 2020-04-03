<?php

namespace TorneLIB\Module\Network\Model;

use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;

interface Wrapper
{
    /**
     * Wrapper constructor.
     */
    public function __construct();

    /**
     * @return WrapperConfig
     */
    public function getConfig();

    /**
     * @param WrapperConfig $config
     * @return mixed
     */
    public function setConfig($config);

    /**
     * @param $username
     * @param $password
     * @param authType $authType
     * @return array
     */
    public function setAuthentication($username, $password, $authType);

    /**
     * @return array
     */
    public function getAuthentication();

    /**
     * @param $url
     * @param array $data
     * @param $method
     * @param int $dataType
     * @return mixed
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL);
}