<?php

namespace TorneLIB\Module\Network\Wrappers;

/**
 * Class SoapClientWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class SoapClientWrapper
{
    private $SOAP;

    public function __construct()
    {
        if (!class_exists('SoapClient')) {
            throw new ExceptionHandler('SOAP unavailable: SoapClient is missing.');
        }
    }

    public function __call($name, $arguments)
    {
    }

    public function __get($name)
    {
    }

    public function request()
    {
    }
}
