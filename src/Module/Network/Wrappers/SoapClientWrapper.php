<?php

namespace TorneLIB\Module\Network\Wrappers;

use Exception;
use SoapClient;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Model\Type\authSource;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Model\Wrapper;

/**
 * Class SoapClientWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 */
class SoapClientWrapper implements Wrapper
{
    /**
     * @var WrapperConfig $CONFIG
     */
    private $CONFIG;

    /**
     * @var SoapClient $soapClient
     */
    private $soapClient;

    /**
     * @var $soapClientResponse
     */
    private $soapClientResponse;

    /**
     * @var array $soapClientContent
     */
    private $soapClientContent = [
        'lastRequest' => null,
        'lastRequestHeaders' => null,
        'lastResponse' => null,
        'lastResponseHeaders' => null,
        'functions' => null,
    ];

    /**
     * @var array $soapWarningException
     */
    private $soapWarningException = ['code' => 0, 'string' => null];

    public function __construct()
    {
        if (!class_exists('SoapClient')) {
            throw new ExceptionHandler('SOAP unavailable: SoapClient is missing.');
        }

        $this->CONFIG = new WrapperConfig();
        $this->getPriorCompatibilityArguments(func_get_args());
    }

    /**
     * Reverse compatibility with v6.0 - returns true if any of the settings here are touched.
     *
     * @param array $funcArgs
     * @return bool
     * @throws Exception
     * @since 6.1.0
     */
    private function getPriorCompatibilityArguments($funcArgs = [])
    {
        $return = false;

        foreach ($funcArgs as $funcIndex => $funcValue) {
            switch ($funcIndex) {
                case 0:
                    if (!empty($funcValue)) {
                        $this->CONFIG->setRequestUrl($funcValue);
                        $return = true;
                    }
                    break;
                case 1:
                    if (is_array($funcValue) && count($funcValue)) {
                        $this->CONFIG->setRequestData($funcValue);
                        $return = true;
                    }
                    break;
                case 2:
                    $this->CONFIG->setRequestMethod($funcValue);
                    $return = true;
                    break;
                case 3:
                    $this->CONFIG->setRequestFlags(is_array($funcValue) ? $funcValue : []);
                    $return = true;
                    break;
            }
        }

        return $return;
    }

    /**
     * @param WrapperConfig $config
     * @return SoapClientWrapper
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
     * @return SoapClientWrapper
     * @since 6.1.0
     */
    public function setAuthentication($username, $password, $authType = authType::ANY)
    {
        $this->CONFIG->setAuthentication($username, $password, $authType, authSource::SOAP);

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
     * SOAP initializer.
     *
     * Prior simpleSoap getSoap() function.
     *
     * @param bool $soapwarningControl
     * @return $this
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getSoapInit($soapwarningControl = false)
    {
        $this->getSoapInitErrorHandler();
        try {
            $this->soapClient = new SoapClient(
                $this->getConfig()->getRequestUrl(),
                $this->getConfig()->getStreamOptions()
            );
        } catch (Exception $soapException) {
            if ((int)$soapException->getCode()) {
                throw $soapException;
            }

            // Trying to prevent dual requests during a soap-transfer. In v6.0, there was dual initializations of
            // soapclient when potential authfail errors occurred.
            if ((int)$this->soapWarningException['code']) {
                $code = $this->getHttpHead($this->soapWarningException['string']);
                $message = $this->getHttpHead($this->soapWarningException['string'], 'message');

                $this->CONFIG->getHttpException(
                    (int)$code > 0 && !empty($message) ? $message : $this->soapWarningException['string'],
                    (int)$code > 0 ? $code : $this->soapWarningException['code'],
                    $soapException,
                    true
                );
            }
        }
        // Reset errorhandle immediately after soaprequest if no exceptions are detected during first request.
        restore_error_handler();

        return $this;
    }

    /**
     * @param $string
     * @param string $returnData
     * @return int|string
     * @since 6.1.0
     */
    private function getHttpHead($string, $returnData = 'code')
    {
        $return = $string;
        $headString = preg_replace(
            '/(.*?) HTTP\/(.*?)\s(.*)$/is',
            '$3',
            trim($string)
        );

        if (preg_match('/\s/', $headString)) {
            $headContent = explode(' ', $headString, 2);

            switch ($returnData) {
                case 'code':
                    if ((int)$headContent[0]) {
                        $return = (int)$headContent[0];
                    }
                    break;
                case 'message':
                    $return = (string)$headContent[1];
                    break;
                default:
                    $return = $string;
                    break;
            }
        }

        return $return;
    }

    /**
     * Initialize SoapExceptions for special occasions.
     *
     * @return $this
     * @since 6.1.0
     */
    private function getSoapInitErrorHandler()
    {
        set_error_handler(function ($errNo, $errStr) {
            $this->soapWarningException['code'] = $errNo;
            $this->soapWarningException['string'] = $errStr;
            restore_error_handler();
            return false;
        }, E_WARNING);

        return $this;
    }

    /**
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getStreamContext()
    {
        $currentStreamContext = $this->getConfig()->getStreamContext();
        if (is_null($currentStreamContext)) {
            // Not properly initialized yet.
            $this->getConfig()->getStreamOptions();
            $currentStreamContext = $this->getConfig()->getStreamContext();
            if (is_resource($currentStreamContext)) {
                $currentStreamContext = stream_context_get_options($currentStreamContext);
            }
        }

        return $currentStreamContext;
    }

    /**
     * Interface Request Method. Barely not in used in this service.
     *
     * @param $url
     * @param array $data
     * @param int $method Not in use.
     * @param int $dataType Not in use, as we are located in the world of SOAP.
     * @return $this|mixed
     */
    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::SOAP)
    {
        if (!empty($url)) {
            $this->CONFIG->setRequestUrl($url);
        }
        if (is_array($data) && count($data)) {
            $this->CONFIG->setRequestData($data);
        }

        if ($this->CONFIG->getRequestDataType() !== $dataType) {
            $this->CONFIG->setRequestDataType($dataType);
        }

        return $this;
    }

    /**
     * @return SoapClientWrapper
     */
    private function setMergedSoapResponse()
    {
        foreach ($this->soapClientContent as $soapMethod => $value) {
            $methodName = sprintf(
                '__get%s',
                ucfirst($soapMethod)
            );
            $this->soapClientContent[$soapMethod] = $this->getFromSoap($methodName);
        }

        return $this;
    }

    /**
     * @param $methodName
     * @return mixed|null
     * @since 6.1.0
     */
    private function getFromSoap($methodName)
    {
        $return = null;

        if (method_exists($this->soapClient, $methodName)) {
            $return = call_user_func_array([$this->soapClient, $methodName], []);
        }

        return $return;
    }

    /**
     * @param $userAgentString
     * @return WrapperConfig
     * @since 6.1.0
     */
    private function setUserAgent($userAgentString)
    {
        return $this->CONFIG->setUserAgent($userAgentString);
    }

    /**
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getUserAgent()
    {
        return $this->CONFIG->getUserAgent();
    }

    /**
     * Dynamic SOAP-requests passing through.
     *
     * @param $name
     * @param $arguments
     * @return SoapClientWrapper
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        $method = substr($name, 0, 3);
        $methodContent = (new Strings())->getCamelCase(substr($name, 3));

        switch (strtolower($method)) {
            case 'get':
                if (isset($this->soapClientContent[$methodContent])) {
                    return $this->soapClientContent[$methodContent];
                } elseif (method_exists($this, $name)) {
                    return call_user_func_array(
                        [
                            $this,
                            $name,
                        ],
                        $arguments
                    );
                }
                break;
            case 'set':
                if (method_exists($this, $name)) {
                    call_user_func_array(
                        [$this, $name],
                        $arguments
                    );

                    return $this;
                }
                break;
            default:
                break;
        }

        if (is_null($this->soapClient)) {
            $this->getSoapInit();
        }

        try {
            if (isset($arguments[0])) {
                $this->soapClientResponse = $this->soapClient->$name($arguments[0]);
            } else {
                $this->soapClientResponse = $this->soapClient->$name();
            }
        } catch (Exception $soapException) {
        }

        // Collect data from soapclient.
        $this->setMergedSoapResponse();

        // Return as the last version, if return exists as a response point, we use this part
        // primarily.
        if (isset($this->soapClientResponse->return)) {
            $return = $this->soapClientResponse->return;
        } else {
            $return = $this->soapClientResponse;
        }

        return $return;
    }
}
