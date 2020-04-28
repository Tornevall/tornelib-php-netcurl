<?php

namespace TorneLIB\Module\Network;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Content;
use TorneLIB\Model\Interfaces\WrapperInterface;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Config\WrapperDriver;
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
     * @var bool
     */
    private $isSoapRequest = false;

    /**
     * @var string $version Internal version.
     */
    private $version;

    /**
     * @var string $selectedWrapper
     */
    private $selectedWrapper;

    public function __construct()
    {
        $this->initializeWrappers();
        return $this;
    }

    /**
     * @since 6.1.0
     */
    private function initializeWrappers()
    {
        WrapperDriver::initializeWrappers();
        $this->CONFIG = new WrapperConfig();
        return $this;
    }

    /**
     * Allows strict identification in user-agent header.
     * @param $activation
     * @param $allowPhpRelease
     * @return NetWrapper
     * @since 6.1.0
     */
    public function setIdentifiers($activation, $allowPhpRelease = false)
    {
        $this->CONFIG->setIdentifiers($activation, $allowPhpRelease);

        return $this;
    }

    /**
     * @inheritDoc
     * @throws \ReflectionException
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
     * Get list of internal wrappers.
     *
     * @return mixed
     * @since 6.1.0
     */
    public function getWrappers()
    {
        return WrapperDriver::getWrappers();
    }

    /**
     * @param WrapperConfig $config
     * @return NetWrapper
     * @since 6.1.0
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
     * Register an external wrapper/module/communicator.
     *
     * @since 6.1.0
     */
    public function register($wrapperClass, $tryFirst = false)
    {
        return WrapperDriver::register($wrapperClass, $tryFirst);
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
                WrapperDriver::getInstanceClass(),
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
                WrapperDriver::getInstanceClass(),
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
                WrapperDriver::getInstanceClass(),
                __FUNCTION__
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL)
    {
        $this->CONFIG->setNetWrapper(true);
        $return = null;
        $requestexternalExecute = null;

        $externalWrapperList = WrapperDriver::getExternalWrappers();
        if (WrapperDriver::getRegisteredWrappersFirst() && count($externalWrapperList)) {
            try {
                $returnable = $this->requestExternalExecute($url, $data, $method, $dataType);
                if (!is_null($returnable)) {
                    return $returnable;
                }
            } catch (ExceptionHandler $requestexternalExecute) {
            }
        }

        // Run internal wrappers.
        if ($hasReturnedRequest = $this->getResultFromInternals(
            $url,
            $data,
            $method,
            $dataType
        )) {
            $return = $hasReturnedRequest;
        };

        $externalWrapperList = WrapperDriver::getExternalWrappers();
        // Internal handles are usually throwing execptions before landing here.
        if (is_null($return) &&
            !WrapperDriver::getRegisteredWrappersFirst() &&
            count($externalWrapperList)
        ) {
            // Last execution should render errors thrown from external executes.
            $returnable = $this->requestExternalExecute($url, $data, $method, $dataType);
            if (!is_null($returnable)) {
                return $returnable;
            }
        }

        $this->getInstantiationException($return, __CLASS__, __FILE__, $requestexternalExecute);

        return $return;
    }

    /**
     * @param $url
     * @param array $data
     * @param int $method
     * @param int $dataType
     * @return mixed|null
     * @throws ExceptionHandler
     */
    private function getResultFromInternals(
        $url,
        $data = [],
        $method = requestMethod::METHOD_GET,
        $dataType = dataType::NORMAL
    ) {
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

        // Example from tornelib-php-drivertest.
        // This allows us to add internal supported drivers without including them in this specific package.
        //$testWrapper = WrapperDriver::getWrapperAllowed('myNameSpace\myDriver');

        if ($dataType === dataType::SOAP && ($this->getProperInstanceWrapper('SoapClientWrapper'))) {
            $this->isSoapRequest = true;
            $this->instance->setConfig($this->getConfig());
            $return = $this->instance->request($url, $data, $method, $dataType);
        } elseif ($this->getProperInstanceWrapper('CurlWrapper')) {
            $this->instance->setConfig($this->getConfig());
            $return = $this->instance->request($url, $data, $method, $dataType);
        } elseif ($this->getProperInstanceWrapper('SimpleStreamWrapper')) {
            $currentConfig = $this->getConfig();
            // Check if auth is properly set, in case default setup is used.
            $currentConfig->setAuthStream();
            $this->instance->setConfig($currentConfig);
            $return = $this->instance->request($url, $data, $method, $dataType);
        }

        return $return;
    }

    /**
     * @param $wrapperName
     * @return mixed|WrapperInterface
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getProperInstanceWrapper($wrapperName)
    {
        $this->instance = WrapperDriver::getWrapperAllowed($wrapperName, true);

        if (!is_null($this->instance)) {
            $this->selectedWrapper = get_class($this->instance);
        }

        return $this->instance;
    }

    /**
     * @param bool $short
     * @return string
     * @since 6.1.0
     */
    public function getCurrentWrapperClass($short = false)
    {
        return $this->CONFIG->getCurrentWrapperClass($short);
    }

    /**
     * Check if return value is null and if, do thrown an exception. This is done if no instances has been successfully
     * created during request.
     *
     * @param $nullValue
     * @param $className
     * @param $functionName
     * @param $requestexternalExecute
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getInstantiationException($nullValue, $className, $functionName, $requestexternalExecute)
    {
        if (is_null($nullValue)) {
            throw new ExceptionHandler(
                sprintf(
                    '%s instantiation failure: No communication wrappers currently available in function/class %s.',
                    $className,
                    $functionName
                ),
                Constants::LIB_NETCURL_NETWRAPPER_NO_DRIVER_FOUND,
                $requestexternalExecute
            );
        }
    }

    /**
     * Initiate an external wrapper request. This actually initiates a "wrapper loop" that runs through
     * each registered wrapper and uses the first that responds correctly. Method is collected here as it
     * runs both in the top of request (if prioritized like that) and in the bottom if developers primarily
     * prefers to use internal classes before their own.
     *
     * @param $url
     * @param array $data
     * @param int $method
     * @param int $dataType
     * @return mixed|null
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function requestExternalExecute(
        $url,
        $data = [],
        $method = requestMethod::METHOD_GET,
        $dataType = dataType::NORMAL
    ) {
        $externalHasErrors = false;
        $externalRequestException = null;
        $returnable = null;
        try {
            $returnable = $this->requestExternal(
                $url,
                $data,
                $method,
                $dataType
            );
        } catch (\Exception $externalRequestException) {
            // Ignore errors here as we have more to go.
            $externalHasErrors = true;
        }
        if (!$externalHasErrors) {
            return $returnable;
        }

        throw new ExceptionHandler(
            sprintf(
                'Internal %s error.',
                __FUNCTION__
            ),
            Constants::LIB_UNHANDLED,
            $externalRequestException
        );
    }

    /**
     * request external execution looper.
     *
     * @param $url
     * @param array $data
     * @param int $method
     * @param int $dataType
     * @return mixed|null
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function requestExternal(
        $url,
        $data = [],
        $method = requestMethod::METHOD_GET,
        $dataType = dataType::NORMAL
    ) {
        $return = null;
        $hasInternalSuccess = false;

        $externalWrapperList = WrapperDriver::getExternalWrappers();
        // Walk through external wrappers.
        foreach ($externalWrapperList as $wrapperClass) {
            $returnable = null;
            try {
                $this->CONFIG->setCurrentWrapper(get_class($wrapperClass));
                $returnable = call_user_func_array(
                    [
                        $wrapperClass,
                        'request',
                    ],
                    [
                        $url,
                        $data,
                        $method,
                        $dataType,
                    ]
                );
            } catch (\Exception $externalException) {

            }
            // Break on first success.
            if (!is_null($returnable)) {
                $hasInternalSuccess = true;
                $return = $returnable;
                break;
            }
        }

        if (!$hasInternalSuccess) {
            throw new ExceptionHandler(
                sprintf(
                    'An error occurred when configured external wrappers tried to communicate with %s.',
                    $url
                ),
                isset($externalException) ? $externalException->getCode() : Constants::LIB_UNHANDLED,
                isset($externalException) ? $externalException : null
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
                if (method_exists($this->instance, $name)) {
                    if ($instanceRequest = call_user_func_array([$this->instance, $name], $arguments)) {
                        return $instanceRequest;
                    }
                } elseif (method_exists($this->CONFIG, $name)) {
                    call_user_func_array(
                        [
                            $this->CONFIG,
                            $name,
                        ], $arguments
                    );
                    break;
                }
                throw new \Exception(
                    sprintf('Undefined function: %s', $name)
                );
                break;
        }
    }
}
