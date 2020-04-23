<?php

namespace TorneLIB\Module\Network;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Content;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Network\Model\WrapperInterface;
use TorneLIB\Module\Network\Wrappers\SoapClientWrapper;
use TorneLIB\Utils\Generic;
use TorneLIB\Utils\Security;

/**
 * Class NetWrapper
 * Taking over from v6.0 MODULE_CURL.
 *
 * @package TorneLIB\Module\Network
 * @version 6.1.0
 */
class NetWrapper implements WrapperInterface
{
    /**
     * @var WrapperConfig $CONFIG
     */
    private $CONFIG;

    /**
     * @var
     */
    private $wrappers;

    /**
     * @var array $internalWrapperList What we support internally.
     */
    private $internalWrapperList = [
        'TorneLIB\Module\Network\Wrappers\CurlWrapper',
        'TorneLIB\Module\Network\Wrappers\StreamWrapper',
        'TorneLIB\Module\Network\Wrappers\SoapClientWrapper',
        'TorneLIB\Module\Network\Wrappers\GuzzleWrapper',
    ];

    private $externalWrapperList = [];

    /**
     * @var bool
     */
    private $isSoapRequest = false;

    /**
     * @var string $version Internal version.
     */
    private $version;

    public function __construct()
    {
        $this->initializeWrappers();
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        $return = $this->version;

        if (empty($return)) {
            $return = (new Generic())->getVersionByClassDoc(__CLASS__);
        }

        return $return;
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    private function initializeWrappers()
    {
        $this->CONFIG = new WrapperConfig();

        foreach ($this->internalWrapperList as $wrapperClass) {
            try {
                $this->wrappers[] = new $wrapperClass();
            } catch (\Exception $wrapperLoadException) {
            }
        }

        return $this->wrappers;
    }

    /**
     * @return bool
     * @since 6.1.0
     */
    public function getIsSoap()
    {
        return $this->isSoapRequest;
    }

    /**
     * @var WrapperInterface $instance The instance is normally the wrapperinterface.
     * @since 6.1.0
     */
    private $instance;

    /**
     * @var
     * @since 6.1.0
     */
    private $instanceClass;

    /**
     * @return mixed
     */
    public function getWrappers()
    {
        return $this->wrappers;
    }

    /**
     * @param WrapperConfig $config
     * @return NetWrapper
     */
    public function setConfig($config)
    {
        /** @var WrapperConfig CONFIG */
        $this->CONFIG = $config;

        return $this;
    }

    /**
     * @return WrapperConfig
     * @since 6.1.0
     */
    public function getConfig()
    {
        return $this->CONFIG;
    }

    /**
     * @param $username
     * @param $password
     * @param int $authType
     * @return NetWrapper
     * @since 6.1.0
     */
    public function setAuthentication($username, $password, $authType = authType::BASIC)
    {
        $this->CONFIG->setAuthentication($username, $password, $authType);

        return $this;
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getAuthentication()
    {
        return $this->CONFIG->getAuthentication();
    }

    /**
     * Register a new wrapper/module/communicator.
     */
    public function register($wrapperNamespace, $wrapperClassName)
    {
        sprintf(
            '%s\%s',
            __NAMESPACE__,
            __CLASS__
        );

        print_R($wrapperClass);
        die;
    }

    /**
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getBody()
    {
        if (method_exists($this->instance, 'getBody')) {
            return $this->instance->getBody();
        }

        throw new ExceptionHandler(
            sprintf(
                '%s instance %s does not support %s.',
                __CLASS__,
                $this->getInstanceClass(),
                __FUNCTION__
            )
        );
    }

    /**
     * @since 6.1.0
     */
    public function getParsed()
    {
        if (method_exists($this->instance, 'getBody')) {
            return $this->instance->getParsed();
        }

        throw new ExceptionHandler(
            sprintf(
                '%s instance %s does not support %s.',
                __CLASS__,
                $this->getInstanceClass(),
                __FUNCTION__
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        if (method_exists($this->instance, 'getCode')) {
            return $this->instance->getCode();
        }

        throw new ExceptionHandler(
            sprintf(
                '%s instance %s does not support %s.',
                __CLASS__,
                $this->getInstanceClass(),
                __FUNCTION__
            )
        );
    }

    /**
     * @param $wrapperNameClass
     * @return mixed
     * @throws ExceptionHandler
     */
    private function getWrapper($wrapperNameClass)
    {
        $return = null;

        foreach ($this->wrappers as $wrapperClass) {
            $currentWrapperClass = get_class($wrapperClass);

            if (
                $currentWrapperClass === sprintf('TorneLIB\Module\Network\Wrappers\%s', $wrapperNameClass) ||
                $currentWrapperClass === $wrapperNameClass
            ) {
                $this->instanceClass = $wrapperNameClass;
                $return = $wrapperClass;
                break;
            }
        }

        if (is_null($return)) {
            throw new ExceptionHandler(
                sprintf(
                    'Could not find a proper NetWrapper (%s) to communicate with!',
                    $wrapperNameClass
                ),
                Constants::LIB_NETCURL_NETWRAPPER_NO_DRIVER_FOUND
            );
        }

        $this->instance = $return;

        return $return;
    }

    /**
     * Returns the instance classname if set and ready.
     *
     * @return string
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getInstanceClass()
    {
        if (empty($this->instanceClass)) {
            throw new ExceptionHandler(
                sprintf(
                    '%s instantiation failure: No wrapper available.',
                    __CLASS__
                ),
                Constants::LIB_NETCURL_NETWRAPPER_NO_DRIVER_FOUND
            );
        }

        return (string)$this->instanceClass;
    }

    /**
     * @inheritDoc
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL)
    {
        $return = null;

        if (preg_match('/\?wsdl|\&wsdl/i', $url)) {
            try {
                Security::getCurrentClassState('SoapClient');
                $dataType = dataType::SOAP;
            } catch (ExceptionHandler $e) {
                $method = requestMethod::METHOD_POST;
                $dataType = dataType::XML;
                if (!is_string($data) && !empty($data)) {
                    $data = (new Content())->getXmlFromArray($data);
                }
            }
        }

        /** @var WrapperInterface $classRequest */
        if ($dataType === dataType::SOAP && ($classRequest = $this->getWrapper('SoapClientWrapper'))) {
            $this->isSoapRequest = true;
            $classRequest->setConfig($this->getConfig());
            $return = $classRequest->request($url, $data, $method, $dataType);
        } elseif ($classRequest = $this->getWrapper('CurlWrapper')) {
            $classRequest->setConfig($this->getConfig());
            $return = $classRequest->request($url, $data, $method, $dataType);
        }

        if (is_null($return)) {
            throw new ExceptionHandler(
                sprintf(
                    '%s instantiation failure: No wrapper available in function %s.',
                    __CLASS__,
                    __FUNCTION__
                ),
                Constants::LIB_NETCURL_NETWRAPPER_NO_DRIVER_FOUND
            );
        }

        return $return;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $requestType = substr($name, 0, 3);

        switch ($name) {
            case 'setAuth':
                // Abbreviation for setAuthentication.
                return call_user_func_array([$this, 'setAuthentication'], $arguments);
            default:
                break;
        }

        switch ($requestType) {
            default:
                if ($instanceRequest = call_user_func_array([$this->instance, $name], $arguments)) {
                    return $instanceRequest;
                }
                throw new \Exception(
                    sprintf('Undefined function: %s', $name)
                );
                break;
        }
    }
}
