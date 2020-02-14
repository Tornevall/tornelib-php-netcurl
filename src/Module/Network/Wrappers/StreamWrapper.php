<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\ExceptionHandler;

/**
 * Class StreamWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class StreamWrapper
{
    private $STREAMS;

    public function __construct()
    {
        throw new ExceptionHandler('Unhandled wrapper: Stream. Make sure the developer checks for existence before loading.');
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
