<?php

namespace TorneLIB\Module\Network\Wrappers;

/**
 * Class CurlWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class CurlWrapper
{
    /**
     * @var resource cURL simple handle
     */
    private $CURL;

    /**
     * @var resource cURL multi handle
     */
    private $MCURL;

    public function __construct()
    {
    }

    public function __call($name, $arguments)
    {
    }

    public function __get($name)
    {
    }
}
