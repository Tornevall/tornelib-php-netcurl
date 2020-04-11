<?php

namespace TorneLIB;

use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\NetWrapper;

//use Zend\Http\Client;

/**
 * Class MODULE_CURL
 *
 *  Passthrough client that v6.0 remember.
 *
 * @package TorneLIB
 * @version 6.1.0
 * @since   6.0.20
 * @deprecated You should consider NetWrapper instead.
 */
class MODULE_CURL
{
    /**
     * @var WrapperConfig $CONFIG
     */
    private $CONFIG;

    /**
     * @var NetWrapper
     */
    private $netWrapper;

    /**
     * @var Flags
     */
    private $flags;

    /**
     * @var array
     */
    private $deprecatedRequest = [
        'get' => requestMethod::METHOD_GET,
        'post' => requestMethod::METHOD_POST,
        'put' => requestMethod::METHOD_PUT,
        'delete' => requestMethod::METHOD_DELETE,
        'head' => requestMethod::METHOD_HEAD,
        'request' => requestMethod::METHOD_REQUEST,
    ];

    public function __construct()
    {
        $this->netWrapper = new NetWrapper();
        $this->flags = new Flags();
        $this->CONFIG = $this->netWrapper->getConfig();

        //$cli = new Client('http://identifier.tornevall.net');
        //echo $cli->send();

        return $this;
    }

    public function doGet($url = '', $postDataType = dataType::NORMAL)
    {
        return $this->netWrapper->request($url, [], requestMethod::METHOD_GET, $postDataType);
    }

    /**
     * @return bool
     * @deprecated Will throw an error in future.
     */
    private function setChain()
    {
        /*throw new ExceptionHandler(
            'Chaining has been removed from netcurl 6.1!',
            Constants::LIB_METHOD_OBSOLETE
        );*/
        return false;
    }

    public function __get($name)
    {
    }

    /**
     * @param $requestType
     * @return requestMethod
     */
    private function getDeprecatedRequest($requestType)
    {
        return isset($this->deprecatedRequest[$requestType]) ? $this->deprecatedRequest[$requestType] : requestMethod::METHOD_GET;
    }

    public function __call($name, $arguments)
    {
        // Ignore failures.
        $requestType = substr($name, 0, 3);
        $requestName = lcfirst(substr($name, 3));
        $deprecatedRequest = substr($name, 0, 2);
        $deprecatedRequestName = lcfirst(substr($name, 2));

        if (method_exists($this, $name)) {
            return call_user_func_array(
                [$this, $name],
                $arguments
            );
        } elseif (method_exists($this->CONFIG, $name)) {
            return call_user_func_array(
                [$this->CONFIG, $name],
                $arguments
            );
        } elseif (preg_match('/^(.*)Flag$/', $name)) {
            return call_user_func_array([$this->flags, $name], $arguments);
        } elseif ($requestType === 'set') {
            $arguments = array_merge([$requestName], $arguments);
            return call_user_func_array([$this->flags, 'setFlag'], $arguments);
        } elseif ($requestType === 'get') {
            $arguments = array_merge([$requestName], $arguments);
            $getFlagResponse = call_user_func_array([$this->flags, 'getFlag'], $arguments);

            if (!is_null($this->netWrapper) && method_exists($this->netWrapper, $name)) {
                return call_user_func_array(
                    [$this->netWrapper, $name],
                    $arguments
                );
            }

            return $getFlagResponse;
        } elseif ($deprecatedRequest === 'do') {
            switch ($deprecatedRequestName) {
                case 'get':
                    $return = $this->netWrapper->request(
                        isset($arguments[0]) ? $arguments[0] : null,
                        [],
                        $this->getDeprecatedRequest('get'),
                        isset($arguments[1]) ? $arguments[1] : null
                    );
                    break;
                default:
                    $return = $this->netWrapper->request(
                        isset($arguments[0]) ? $arguments[0] : null,
                        isset($arguments[1]) ? $arguments[1] : null,
                        isset($arguments[2]) ? $this->getDeprecatedRequest(
                            $arguments[2]
                        ) : $this->getDeprecatedRequest('post'),
                        isset($arguments[3]) ? $arguments[3] : null
                    );
                    break;
            }
            return $return;
        }
    }
}
