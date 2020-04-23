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
    private $wrappers = [];

    /**
     * @var array $internalWrapperList What we support internally.
     */
    private $internalWrapperList = [
        'TorneLIB\Module\Network\Wrappers\CurlWrapper',
        'TorneLIB\Module\Network\Wrappers\StreamWrapper',
        'TorneLIB\Module\Network\Wrappers\SoapClientWrapper',
        'TorneLIB\Module\Network\Wrappers\GuzzleWrapper',
    ];

    /**
     * @var bool $useRegisteredWrappersFirst If true, make NetWrapper try to use those wrappers first.
     */
    private $useRegisteredWrappersFirst = false;

    /**
     * @var array $externalWrapperList List of self developed wrappers to use if nothing else works.
     */
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

        return $this;
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
            if (!empty($wrapperClass) &&
                !in_array($wrapperClass, $this->wrappers)
            ) {
                try {
                    $this->wrappers[] = new $wrapperClass();
                } catch (\Exception $wrapperLoadException) {
                }
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
     * Get list of internal wrappers.
     *
     * @return mixed
     * @since 6.1.0
     */
    public function getWrappers()
    {
        return $this->wrappers;
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
        if (!is_object($wrapperClass)) {
            throw new ExceptionHandler(
                sprintf(
                    'Unable to register wrong class type in %s.',
                    __CLASS__
                ),
                Constants::LIB_CLASS_UNAVAILABLE
            );
        }

        $this->useRegisteredWrappersFirst = $tryFirst;
        $this->registerClassInterface($wrapperClass);

        return $this;
    }

    /**
     * Register external wrapper class as useble if it implements the wrapper interface.
     *
     * @param $wrapperClass
     * @since 6.1.0
     */
    private function registerClassInterface($wrapperClass)
    {
        $badClass = false;

        if (!in_array($wrapperClass, $this->externalWrapperList) &&
            $this->registerCheckImplements($wrapperClass)
        ) {
            $this->externalWrapperList[] = $wrapperClass;
        } else {
            $badClass = true;
        }

        $this->registerCheckBadClass($badClass, $wrapperClass);
    }

    /**
     * @param $badClass
     * @param $wrapperClass
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function registerCheckBadClass($badClass, $wrapperClass)
    {
        if ($badClass) {
            throw new ExceptionHandler(
                sprintf(
                    'Unable to register class %s in %s with wrong interface.',
                    get_class($wrapperClass),
                    __CLASS__
                ),
                Constants::LIB_CLASS_UNAVAILABLE
            );
        }
    }

    /**
     * Checks if registering class implements WrapperInterface.
     *
     * @param $wrapperClass
     * @since 6.1.0
     */
    private function registerCheckImplements($wrapperClass)
    {
        $implements = class_implements($wrapperClass);

        return in_array('TorneLIB\Model\Interfaces\WrapperInterface', $implements);
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
     * Find found if internal wrapper is available and return it.
     *
     * @param $wrapperNameClass
     * @param bool $testOnly Test wrapper only. Meaning: Do not throw exceptions during control.
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getWrapper($wrapperNameClass, $testOnly = false)
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

        if (!$testOnly && !is_object($return)) {
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
        $requestexternalExecute = null;

        if ($this->useRegisteredWrappersFirst && count($this->externalWrapperList)) {
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

        // Internal handles are usually throwing execptions before landing here.
        if (is_null($return) &&
            !$this->useRegisteredWrappersFirst &&
            count($this->externalWrapperList)
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

        /** @var WrapperInterface $classRequest */
        if ($dataType === dataType::SOAP &&
            ($classRequest = $this->getWrapperAllowed('SoapClientWrapper'))
        ) {
            $this->isSoapRequest = true;
            $classRequest->setConfig($this->getConfig());
            $return = $classRequest->request($url, $data, $method, $dataType);
        } elseif ($classRequest = $this->getWrapperAllowed('CurlWrapper')) {
            $classRequest->setConfig($this->getConfig());
            $return = $classRequest->request($url, $data, $method, $dataType);
        }

        return $return;
    }

    /**
     * Returns proper wrapper for internal wrapper requests, depending on external available wrappers.
     *
     * @param $wrapperName
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getWrapperAllowed($wrapperName)
    {
        // If there are no available external wrappers, let getWrapper do its actions and throw exceptions if
        // the internal wrapper fails to load.
        if (!count($this->externalWrapperList)) {
            $return = $this->getWrapper($wrapperName);
        } else {
            // If there are available external wrappers, just try to load external wrapper and proceed
            // without noise on failures, as we'd like to try to use the externals first.
            $return = $this->getWrapper($wrapperName, true);
        }

        return $return;
    }

    /**
     * Check if return value is null and if, do thrown an exception. This is done if no instances has been successfully
     * created during request.
     *
     * @param $nullValue
     * @param $className
     * @param $functioName
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getInstantiationException($nullValue, $className, $functioName, $requestexternalExecute)
    {
        if (is_null($nullValue)) {
            throw new ExceptionHandler(
                sprintf(
                    '%s instantiation failure: No wrapper available in function %s.',
                    $className,
                    $functioName
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

        // Walk through external wrappers.
        foreach ($this->externalWrapperList as $wrapperClass) {
            $returnable = null;
            try {
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
