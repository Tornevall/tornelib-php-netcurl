<?php

namespace TorneLIB\Module\Network\Wrappers;

use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Config\WrapperConfig;
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
    private $CONFIG;

    /**
     * @var resource cURL simple handle
     */
    private $CURL;

    /**
     * @var resource cURL multi handle
     */
    private $MCURL;

    /**
     * CurlWrapper constructor.
     *
     * @throws ExceptionHandler
     */
    public function __construct()
    {
        $this->CONFIG = new WrapperConfig();
        $this->getPriorCompatibilityArguments(func_get_args());
        // Make sure there are available drivers before using the wrapper.
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            throw new ExceptionHandler('curl unavailable: curl_init and/or curl_exec not found');
        }
    }

    /**
     * Reverse compatibility with v6.0
     *
     * @param array $funcArgs
     */
    private function getPriorCompatibilityArguments($funcArgs = [])
    {
        foreach ($funcArgs as $funcIndex => $funcValue) {
            switch ($funcIndex) {
                case 0:
                    if (!empty($funcValue)) {
                        $this->CONFIG->setRequestUrl($funcValue);
                    }
                    break;
                case 1:
                    if (is_array($funcValue) && count($funcValue)) {
                        $this->CONFIG->setRequestVars($funcValue);
                    }
                    break;
                case 2:

                    break;
            }
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
