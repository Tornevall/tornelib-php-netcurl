<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Module\Network\Model\Wrapper;

/**
 * Class CurlWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class CurlWrapper implements Wrapper
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

    public function request()
    {
    }
}
