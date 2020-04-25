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
     * @var bool $useRegisteredWrappersFirst If true, make NetWrapper try to use those wrappers first.
     */
    private $useRegisteredWrappersFirst = false;

    private $wrappers = [];

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
     * @since 6.1.0
     */
    private function initializeWrappers()
    {
        WrapperDriver::initializeWrappers();
        $this->CONFIG = new WrapperConfig();
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
        WrapperDriver::register($wrapperClass, $tryFirst);

        return $this;
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

        /** @var WrapperInterface $classRequest */
        if ($dataType === dataType::SOAP &&
            ($classRequest = WrapperDriver::getWrapperAllowed('SoapClientWrapper'))
        ) {
            $this->isSoapRequest = true;
            $classRequest->setConfig($this->getConfig());
            $return = $classRequest->request($url, $data, $method, $dataType);
        } elseif ($classRequest = WrapperDriver::getWrapperAllowed('CurlWrapper')) {
            $classRequest->setConfig($this->getConfig());
            $return = $classRequest->request($url, $data, $method, $dataType);
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
