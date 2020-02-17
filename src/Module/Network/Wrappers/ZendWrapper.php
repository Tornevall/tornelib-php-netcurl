<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\ExceptionHandler;
use Zend\Http\Client;

class ZendWrapper
{
    public function __construct()
    {
        if (!class_exists('Zend\Http\Client')) {
            throw new ExceptionHandler('zend unavailable: Zend\Http\Client not loaded');
        }
        //$cli = new Client('http://identifier.tornevall.net');
    }
}
