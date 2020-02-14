<?php

namespace TorneLIB\Module\Config;

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
    private $requestPostMethod;

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * @param string $requestUrl
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    /**
     * @return array
     */
    public function getRequestVars()
    {
        return $this->requestVars;
    }

    /**
     * @param array $requestVars
     */
    public function setRequestVars($requestVars)
    {
        $this->requestVars = $requestVars;
    }
}
