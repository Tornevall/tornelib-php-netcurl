<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Helpers\Version;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\requestMethod;
use TorneLIB\Module\Network\Model\WrapperInterface;
use TorneLIB\Utils\Generic;

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

/**
 * Class SimpleWrapper Fetching tool in the simplest form. Using file_get_contents.
 *
 * @package TorneLIB\Module\Network\Wrappers
 * @version 6.1.0
 */
class SimpleWrapper implements WrapperInterface
{
    /**
     * @var WrapperConfig $CONFIG
     * @version 6.1.0
     */
    private $CONFIG;

    public function __construct()
    {
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
     * @param WrapperConfig $config
     * @return SimpleWrapper
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
     * @return SimpleWrapper
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

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        // TODO: Implement getCode() method.
    }
}
