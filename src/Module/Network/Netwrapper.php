<?php

namespace TorneLIB\Module\Network;

use TorneLIB\Module\Network\Wrappers\Curlwrapper;
use TorneLIB\Module\Network\Wrappers\Soapclientwrapper;
use TorneLIB\Module\Network\Wrappers\Streamwrapper;

/**
 * Class Netwrapper
 *
 * Module bridge.
 *
 * @package TorneLIB\Module\Network
 */
class Netwrapper
{
    private $wrappers;

    public function __construct()
    {
        $this->getInternalWrappers();
    }

    private function getInternalWrappers()
    {
        $this->wrappers[] = new Curlwrapper();
        $this->wrappers[] = new Streamwrapper();
        if (class_exists('SoapClient')) {
            $this->wrappers[] = new Soapclientwrapper();
        }
    }

    /**
     * @param $username
     * @param $password
     */
    public function setAuthentication($username, $password)
    {
    }

    public function __call($name, $arguments)
    {
        switch ($name) {
            case 'setAuth':
                // Abbreviation for setAuthentication.
                return call_user_func_array(array($this, 'setAuthentication'), $arguments);
            default:
                throw new \Exception(sprintf('Undefined function: %s', $name));
                break;
        }
    }
}