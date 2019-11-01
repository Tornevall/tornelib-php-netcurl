<?php

namespace TorneLIB\Module\Network;

use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\Module\Network\Wrappers\SoapClientWrapper;
use TorneLIB\Module\Network\Wrappers\StreamWrapper;

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

    public function __construct()
    {
        $this->getInternalWrappers();
    }

    private function getInternalWrappers()
    {
        $this->wrappers[] = new CurlWrapper();
        $this->wrappers[] = new StreamWrapper();
        if (class_exists('SoapClient')) {
            $this->wrappers[] = new SoapClientWrapper();
        }
    }

    /**
     * @param $username
     * @param $password
     */
    public function setAuthentication($username, $password)
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
