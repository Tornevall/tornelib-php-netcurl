<?php

namespace TorneLIB\Module\Network;

/**
 * Class NetWrapper
 *
 * Module bridge.
 *
 * @package TorneLIB\Module\Network
 */
class NetWrapper
{
    private $wrappers;

    /**
     * @var array $internalWrapperList What we support internally.
     */
    private $internalWrapperList = [
        'TorneLIB\Module\Network\Wrappers\CurlWrapper',
        'TorneLIB\Module\Network\Wrappers\StreamWrapper',
        'TorneLIB\Module\Network\Wrappers\SoapClientWrapper',
        'TorneLIB\Module\Network\Wrappers\GuzzleWrapper',
    ];

    public function __construct()
    {
        $this->initializeWrappers();
    }

    /**
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    private function initializeWrappers()
    {
        foreach ($this->internalWrapperList as $wrapperClass) {
            try {
                $this->wrappers[] = new $wrapperClass();
            } catch (\Exception $wrapperLoadException) {
            }
        }

        return $this->wrappers;
    }

    /**
     * @return mixed
     */
    public function getWrappers()
    {
        return $this->wrappers;
    }

    /**
     * @param $username
     * @param $password
     */
    public function setAuthentication($username, $password)
    {
    }

    public function getAuthentication()
    {

    }

    /**
     * Return configuration for current used wrapper.
     */
    public function getConfig()
    {

    }

    /**
     * Register a new wrapper/module/communicator.
     */
    public function register()
    {
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        switch ($name) {
            case 'setAuth':
                // Abbreviation for setAuthentication.
                return call_user_func_array([$this, 'setAuthentication'], $arguments);
            default:
                throw new \Exception(
                    sprintf('Undefined function: %s', $name)
                );
                break;
        }
    }
}
