<?php

namespace TorneLIB\Module\Network\Wrappers;

use Exception;
use SoapClient;
use SoapFault;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Model\Type\authSource;
use TorneLIB\Model\Type\authType;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\GenericParser;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Model\Interfaces\WrapperInterface;
use TorneLIB\Utils\Generic;
use TorneLIB\Utils\Ini;
use TorneLIB\Utils\Security;

/**
 * Class SoapClientWrapper
 *
 * @package TorneLIB\Module\Network\Wrappers
 * @version 6.1.0
 */
class SoapClientWrapper implements WrapperInterface
{
    /**
     * @var WrapperConfig $CONFIG
     * @since 6.1.0
     */
    private $CONFIG;

    /**
     * @var SoapClient $soapClient
     * @since 6.1.0
     */
    private $soapClient;

    /**
     * @var $soapClientResponse
     * @since 6.1.0
     */
    private $soapClientResponse;

    /**
     * @var array $soapClientContent
     * @since 6.1.0
     */
    private $soapClientContent = [
        'lastRequest' => null,
        'lastRequestHeaders' => null,
        'lastResponse' => null,
        'lastResponseHeaders' => null,
        'functions' => null,
    ];

    /**
     * The header that the soapResponse are returning, converted to an array.
     *
     * @var array $responseHeaderArray
     * @since 6.1.0
     */
    private $responseHeaderArray = [];

    /**
     * @var array $soapWarningException
     * @since 6.1.0
     */
    private $soapWarningException = ['code' => 0, 'string' => null];

    /**
     * SoapClientWrapper constructor.
     * @throws ExceptionHandler
     */
    public function __construct()
    {
        Security::getCurrentClassState('SoapClient');

        $this->CONFIG = new WrapperConfig();
        $this->CONFIG->setSoapRequest(true);
        $this->getPriorCompatibilityArguments(func_get_args());
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
     * Reverse compatibility with v6.0 - returns true if any of the settings here are touched.
     * Main function as it is duplicated is moved into WrapprConfig->getCompatibilityArguments()
     *
     * @param array $funcArgs
     * @return bool
     * @throws Exception
     * @since 6.1.0
     */
    private function getPriorCompatibilityArguments($funcArgs = [])
    {
        return $this->CONFIG->getCompatibilityArguments($funcArgs);
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getLastRequest() {
        return (string)$this->soapClientContent['lastRequest'];
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getLastRequestHeaders() {
        return (string)$this->soapClientContent['lastRequestHeaders'];
    }

    /**
     * @return bool
     * @since 6.1.0;
     */
    public function getLastResponse() {
        return (string)$this->soapClientContent['lastResponse'];
    }

    /**
     * @return string
     * @since 6.1.0
     */
    public function getLastResponseHeaders() {
        return (string)$this->soapClientContent['lastResponseHeaders'];
    }

    /**
     * Returns an array of function from the soapcall.
     *
     * @return array
     * @since 6.1.0
     */
    public function getFunctions() {
        return (array)$this->soapClientContent['functions'];
    }

    /**
     * @param WrapperConfig $config
     * @return SoapClientWrapper
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
     * @throws ExceptionHandler
     * @throws SoapFault
     * @since 6.1.0
     */
    private function getSoapClient()
    {
        $this->getSoapInitErrorHandler();
        if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
            $this->soapClient = new SoapClient(
                $this->getConfig()->getRequestUrl(),
                $this->getConfig()->getStreamOptions()
            );
        } else {
            // Suppress fatals in older releases.
            $this->soapClient = @(new SoapClient(
                $this->getConfig()->getRequestUrl(),
                $this->getConfig()->getStreamOptions()
            ));
        }
    }

    /**
     * SOAP initializer.
     * Formerly known as a simpleSoap getSoap() variant.
     *
     * @param bool $soapwarningControl
     * @return $this
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getSoapInit($soapwarningControl = false)
    {
        try {
            $this->getSoapClient();
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
                    null,
                    $this,
                    true
                );
            }
        }

        // Restore the errorhandler immediately after soaprequest if no exceptions are detected during first request.
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
        return GenericParser::getHttpHead($string, $returnData);
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
        }

        if (is_resource($currentStreamContext)) {
            $currentStreamContext = stream_context_get_options($currentStreamContext);
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
     * @since 6.1.0
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
     * @since 6.1.0
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
     * @param $methodContent
     * @param $name
     * @param $arguments
     * @return mixed
     * @since 6.1.0
     */
    private function getMagicGettableCall($methodContent, $name, $arguments)
    {
        $return = null;

        if (isset($this->soapClientContent[$methodContent])) {
            $return = $this->soapClientContent[$methodContent];
        } elseif (method_exists($this, $name)) {
            $return = call_user_func_array(
                [
                    $this,
                    $name,
                ],
                $arguments
            );
        }

        return $return;
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     * @since 6.1.0
     */
    private function getMagicSettableCall($name, $arguments)
    {
        $return = null;

        if (method_exists($this, $name)) {
            call_user_func_array(
                [
                    $this,
                    $name,
                ],
                $arguments
            );

            $return = $this;
        } elseif (method_exists($this->CONFIG, $name)) {
            call_user_func_array(
                [
                    $this->CONFIG,
                    $name,
                ],
                $arguments
            );

            $return = $this;
        }

        return $return;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws Exception
     * @since 6.1.0
     */
    private function execSoap($name, $arguments)
    {
        $return = null;

        try {
            // Giving the soapcall a more natural touch with call_user_func_array. Besides, this also means
            // we don't have to check for arguments.
            $return = call_user_func_array(array($this->soapClient, $name), $arguments);
        } catch (Exception $soapFault) {
            // Public note: Those exceptions may be thrown by the soap-api or when the wsdl is cache and there is
            // for example authorization problems. This is why the soapResponse is fetched and analyzed before
            // giving up.

            // Initialize a merged soapResponse of what's left in this exception - and to see if it was a real
            // api request or a local one.
            $this->setMergedSoapResponse();

            if (!is_null($this->soapClientContent['lastResponseHeaders'])) {
                // Pick up the http-head response from the soapResponseHeader.
                $httpHeader = $this->getHeader('http');

                // Check if it is time to throw something specific.
                $this->CONFIG->getHttpException(
                    $this->getHttpHead($httpHeader, 'message'),
                    $this->getHttpHead($httpHeader),
                    $soapFault,
                    $this
                );
            }

            // Continue throw the soapFault as it.
            throw $soapFault;
        }

        return $return;
    }

    /**
     * Dynamically fetch responses from a soapClientResponse.
     * @param $soapClientResponse
     * @return mixed
     * @since 6.1.0
     */
    private function getSoapResponse($soapClientResponse)
    {
        if (isset($soapClientResponse->return)) {
            $return = $soapClientResponse->return;
        } else {
            $return = $soapClientResponse;
        }

        return $return;
    }

    /**
     * @return int
     * @since 6.1.0
     */
    public function getCode() {
       return (int)$this->getHttpHead($this->getHeader('http'));
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this|mixed|null
     * @since 6.1.0
     */
    private function getInternalMagics($name, $arguments)
    {
        $return = null;

        $method = substr($name, 0, 3);
        $methodContent = (new Strings())->getCamelCase(substr($name, 3));

        switch (strtolower($method)) {
            case 'get':
                $getResponse = $this->getMagicGettableCall($methodContent, $name, $arguments);
                if (!is_null($getResponse)) {
                    return $getResponse;
                }
                break;
            case 'set':
                $getResponse = $this->getMagicSettableCall($name, $arguments);
                if (!is_null($getResponse)) {
                    return $getResponse;
                }
                break;
            default:
                break;
        }

        return $return;
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getParsed()
    {
        return $this->getSoapResponse($this->soapClientResponse);
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getBody()
    {
        return $this->getLastResponse();
    }

    /**
     * @param bool $asArray
     * @param bool $lCase
     * @return mixed
     * @since 6.1.0
     */
    public function getHeaders($asArray = false, $lCase = false)
    {
        $return = $this->getLastResponseHeaders();

        if ($asArray) {
            $return = $this->getHeaderArray($this->getLastResponseHeaders(), $lCase);
        }

        return $return;
    }

    /**
     * @param $header
     * @param bool $lCase
     * @return array
     * @since 6.1.0
     */
    private function getHeaderArray($header, $lCase = false)
    {
        $this->responseHeaderArray = [];

        if (is_string($header)) {
            $headerSplit = explode("\n", $header);
            if (is_array($headerSplit)) {
                foreach ($headerSplit as $headerRow) {
                    $this->getHeaderRow($headerRow, $lCase);
                }
            }
        }

        return $this->responseHeaderArray;
    }

    /**
     * @param $header
     * @param bool $lCase
     * @return int
     * @since 6.1.0
     */
    private function getHeaderRow($header, $lCase = false)
    {
        $headSplit = explode(':', $header, 2);
        $spacedSplit = explode(' ', $header, 2);

        if (count($headSplit) < 2) {
            if (count($spacedSplit) > 1) {
                $splitName = !$lCase ? $spacedSplit[0] : strtolower($spacedSplit[0]);

                if (preg_match('/^http\/(.*?)$/i', $splitName)) {
                    $httpSplitName = explode("/", $splitName, 2);
                    $realSplitName = !$lCase ? $httpSplitName[0] : strtolower($httpSplitName[0]);

                    if (!isset($this->responseHeaderArray[$realSplitName])) {
                        $this->responseHeaderArray[$realSplitName] = trim($spacedSplit[1]);
                    } else {
                        $this->responseHeaderArray[$realSplitName][] = trim($spacedSplit[1]);
                    }
                }

                $this->responseHeaderArray[$splitName][] = trim($spacedSplit[1]);
            }
            return strlen($header);
        }

        $splitName = !$lCase ? $headSplit[0] : strtolower($headSplit[0]);
        $this->responseHeaderArray[$splitName][] = trim($headSplit[1]);
        return strlen($header);
    }

    /**
     * @param null $key
     * @return mixed
     * @since 6.1.0
     */
    private function getHeader($key = null)
    {
        if (is_null($key)) {
            $return = $this->getHeaders();
        } else {
            $return = $this->getHeaders(true, true);
        }

        if (isset($return[strtolower($key)])) {
            $return = $return[strtolower($key)];
        }

        return $return;
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
        if (null !== ($internalResponse = $this->getInternalMagics($name, $arguments))) {
            return $internalResponse;
        }

        // Making sure we initialize the soapclient if not already done. Set higher priority for internal requests
        // and configuration.
        if (is_null($this->soapClient)) {
            $this->getSoapInit();
        }

        $this->soapClientResponse = $this->execSoap($name, $arguments);
        $this->setMergedSoapResponse();

        // Return as the last version, if return exists as a response point, we use this part primarily.
        return $this->getSoapResponse($this->soapClientResponse);
    }
}
