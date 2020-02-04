<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Network\Model\Wrapper;

/**
 * Class CurlWrapper.
 *
 * Wrapper to make calls directly to the curl engine. This should not be used primarily if auto detection is the
 * preferred way to fetch data.
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
        // Make sure our wrapper exists before using it.
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            throw new ExceptionHandler('curl unavailable: curl_init and/or curl_exec not found');
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
