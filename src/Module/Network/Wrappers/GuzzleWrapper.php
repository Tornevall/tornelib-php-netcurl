<?php

namespace TorneLIB\Module\Network\Wrappers;
use GuzzleHttp\Client
use GuzzleHttp\Handler\StreamHandler;

class GuzzleWrapper
{
    public function __construct()
    {
        if (!class_exists('GuzzleHttp\Client') || !class_exists('GuzzleHttp\Handler\StreamHandler')) {
            throw new ExceptionHandler('zend unavailable: Zend\Http\Client not loaded');
        }
	    //$cli = new Client('http://identifier.tornevall.net');
    }
}
