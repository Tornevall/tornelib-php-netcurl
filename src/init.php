<?php

namespace TorneLIB;

use TorneLIB\Module\Network\Netwrapper;
use Zend\Http\Client;

/**
 * Class MODULE_CURL
 *
 *  Passthrough client that v6.0 remember.
 *
 * @package TorneLIB
 */
class MODULE_CURL
{
    private $module;

    public function __construct()
    {
        $this->module = new Netwrapper();
        $cli = new Client('http://identifier.tornevall.net');
        echo $cli->send();
    }

    public function __get($name)
    {
    }

    public function __call($name, $arguments)
    {
    }
}