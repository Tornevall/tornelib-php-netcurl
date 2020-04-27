<?php

namespace TorneLIB;

if (!class_exists('NETCURL_DRIVER_GUZZLEHTTP', NETCURL_CLASS_EXISTS_AUTOLOAD) &&
    !class_exists('TorneLIB\NETCURL_DRIVER_GUZZLEHTTP', NETCURL_CLASS_EXISTS_AUTOLOAD)
) {
    /**
     * Class NETCURL_DRIVER_GUZZLEHTTP Network communications driver detection
     * Inspections for classes and namespaces is ignored as they are dynamically loaded when they do exist.
     *
     * @package TorneLIB
     * @deprecated Replaced with PSR4 compliances in v6.1
     */
    class NETCURL_DRIVER_GUZZLEHTTP implements NETCURL_DRIVERS_INTERFACE
    {

        /** @var NETCURL_NETWORK_DRIVERS $DRIVER_ID */
        private $DRIVER_ID = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET;

        /** @var array Inbound parameters in the format array, object or whatever this driver takes */
        private $PARAMETERS = [];

        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        /** @var \GuzzleHttp\Client $DRIVER The class for where everything happens */
        private $DRIVER;

        /** @var MODULE_NETWORK $NETWORK Network driver for using exceptions, etc */
        private $NETWORK;

        /** @var string $POST_CONTENT_TYPE Content type */
        private $POST_CONTENT_TYPE = '';

        /** @var string $REQUEST_URL */
        private $REQUEST_URL = '';

        /** @var NETCURL_POST_METHODS */
        private $POST_METHOD = NETCURL_POST_METHODS::METHOD_GET;

        /** @var array $POST_DATA ... or string, or object, etc */
        private $POST_DATA;

        /** @var NETCURL_POST_DATATYPES */
        private $POST_DATA_TYPE = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET;

        /** @var $WORKER_DATA */
        private $WORKER_DATA = [];

        /** @var int $HTTP_STATUS */
        private $HTTP_STATUS = 0;

        /** @var string $HTTP_MESSAGE */
        private $HTTP_MESSAGE = '';

        /** @var bool $HAS_AUTHENTICATION Set if there's authentication configured */
        private $HAS_AUTHENTICATION = false;

        /**
         * @var array $POST_AUTH_DATA
         */
        private $POST_AUTH_DATA = [];

        /** @var string $RESPONSE_RAW */
        private $RESPONSE_RAW = '';

        /** @var array $GUZZLE_POST_OPTIONS Post options for Guzzle */
        private $GUZZLE_POST_OPTIONS;


        public function __construct($parameters = null)
        {
            $this->NETWORK = new MODULE_NETWORK();
            if (!is_null($parameters)) {
                $this->setParameters($parameters);
            }
        }

        public function setDriverId($driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET)
        {
            $this->DRIVER_ID = $driverId;
        }

        public function setParameters($parameters = [])
        {
            $this->PARAMETERS = $parameters;
        }


        private function initializeClass()
        {
            if ($this->DRIVER_ID == NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP) {
                if (class_exists('GuzzleHttp\Client', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
                    /** @noinspection PhpUndefinedClassInspection */
                    /** @noinspection PhpUndefinedNamespaceInspection */
                    $this->DRIVER = new \GuzzleHttp\Client;
                }
            } elseif ($this->DRIVER_ID === NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM) {
                if (class_exists('GuzzleHttp\Handler\StreamHandler', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
                    /** @noinspection PhpUndefinedClassInspection */
                    /** @noinspection PhpUndefinedNamespaceInspection */
                    /** @var \GuzzleHttp\Handler\StreamHandler $streamHandler */
                    $streamHandler = new \GuzzleHttp\Handler\StreamHandler();
                    /** @noinspection PhpUndefinedClassInspection */
                    /** @noinspection PhpUndefinedNamespaceInspection */
                    /** @var \GuzzleHttp\Client */
                    $this->DRIVER = new \GuzzleHttp\Client(['handler' => $streamHandler]);
                }
            }
        }

        public function getContentType()
        {
            return $this->POST_CONTENT_TYPE;
        }

        public function setContentType($setContentTypeString = 'application/json; charset=utf-8')
        {
            $this->POST_CONTENT_TYPE = $setContentTypeString;
        }

        /**
         * @param null $Username
         * @param null $Password
         * @param int $AuthType
         */
        public function setAuthentication(
            $Username = null,
            $Password = null,
            $AuthType = NETCURL_AUTH_TYPES::AUTHTYPE_BASIC
        ) {
            $this->POST_AUTH_DATA['Username'] = $Username;
            $this->POST_AUTH_DATA['Password'] = $Password;
            $this->POST_AUTH_DATA['Type'] = $AuthType;
        }

        /**
         * @return array
         */
        public function getAuthentication()
        {
            return $this->POST_AUTH_DATA;
        }

        /**
         * @return array
         */
        public function getWorker()
        {
            return $this->WORKER_DATA;
        }

        /**
         * @return int
         */
        public function getStatusCode()
        {
            return $this->HTTP_STATUS;
        }

        /**
         * @return string
         */
        public function getStatusMessage()
        {
            return $this->HTTP_MESSAGE;
        }

        /**
         * Guzzle Renderer
         *
         * @return $this|NETCURL_DRIVER_GUZZLEHTTP
         * @throws \Exception
         */
        private function getGuzzle()
        {
            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedNamespaceInspection */
            /** @var $gResponse \GuzzleHttp\Psr7\Response */
            $gResponse = null;
            $this->RESPONSE_RAW = null;
            $gBody = null;

            $this->GUZZLE_POST_OPTIONS = $this->getPostOptions();

            $gRequest = $this->getGuzzleRequest();
            if (!is_null($gRequest)) {
                $this->getRenderedGuzzleResponse($gRequest);
            } else {
                throw new \Exception(
                    NETCURL_CURL_CLIENTNAME . " streams for guzzle is probably missing as I can't find the request method in the current class",
                    $this->NETWORK->getExceptionCode('NETCURL_GUZZLESTREAM_MISSING')
                );
            }

            return $this;
        }

        /**
         * @param $gRequest
         * @return NETCURL_DRIVER_GUZZLEHTTP
         * @throws \Exception
         */
        private function getRenderedGuzzleResponse($gRequest)
        {
            $this->WORKER_DATA = ['worker' => $this->DRIVER, 'request' => $gRequest];
            if (method_exists($gRequest, 'getHeaders')) {
                $gHeaders = $gRequest->getHeaders();
                /** @noinspection PhpUndefinedMethodInspection */
                $gBody = $gRequest->getBody()->getContents();
                /** @noinspection PhpUndefinedMethodInspection */
                $this->HTTP_STATUS = $gRequest->getStatusCode();
                /** @noinspection PhpUndefinedMethodInspection */
                $this->HTTP_MESSAGE = $gRequest->getReasonPhrase();
                /** @noinspection PhpUndefinedMethodInspection */
                $this->RESPONSE_RAW .= "HTTP/" . $gRequest->getProtocolVersion() . " " . $this->HTTP_STATUS . " " . $this->HTTP_MESSAGE . "\r\n";
                $this->RESPONSE_RAW .= "X-NetCurl-ClientDriver: " . $this->DRIVER_ID . "\r\n";
                if (is_array($gHeaders)) {
                    foreach ($gHeaders as $hParm => $hValues) {
                        $this->RESPONSE_RAW .= $hParm . ": " . implode("\r\n", $hValues) . "\r\n";
                    }
                }
                $this->RESPONSE_RAW .= "\r\n" . $gBody;

                // Prevent problems during authorization. Unsupported media type checks defaults to application/json
                if ($this->HAS_AUTHENTICATION && $this->HTTP_STATUS == 415) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $contentTypeRequest = $gRequest->getHeader('content-type');
                    if (empty($contentTypeRequest)) {
                        $this->setContentType();
                    } else {
                        $this->setContentType($contentTypeRequest);
                    }

                    return $this->getGuzzle();
                }
            } else {
                throw new \Exception(
                    NETCURL_CURL_CLIENTNAME . "-" . __FUNCTION__ . " exception: Guzzle driver missing proper methods like getHeaders(), can not render response",
                    $this->NETWORK->getExceptionCode('NETCURL_GUZZLE_RESPONSE_EXCEPTION')
                );
            }
            return $this;
        }

        /**
         * Render postdata
         */
        private function getPostOptions()
        {
            $postOptions = [];
            $postOptions['headers'] = [];
            $contentType = $this->getContentType();

            if ($this->POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_JSON) {
                $postOptions['headers']['Content-Type'] = 'application/json; charset=utf-8';
                if (is_string($this->POST_DATA)) {
                    $jsonPostData = @json_decode($this->POST_DATA);
                    if (is_object($jsonPostData)) {
                        $this->POST_DATA = $jsonPostData;
                    }
                }
                $postOptions['json'] = $this->POST_DATA;
            } else {
                if (is_array($this->POST_DATA)) {
                    $postOptions['form_params'] = $this->POST_DATA;
                }
            }

            if (isset($this->POST_AUTH_DATA['Username'])) {
                $this->HAS_AUTHENTICATION = true;
                if ($this->POST_AUTH_DATA['Type'] == NETCURL_AUTH_TYPES::AUTHTYPE_BASIC) {
                    $postOptions['headers']['Accept'] = '*/*';
                    if (!empty($contentType)) {
                        $postOptions['headers']['Content-Type'] = $contentType;
                    }
                    $postOptions['auth'] = [
                        $this->POST_AUTH_DATA['Username'],
                        $this->POST_AUTH_DATA['Password'],
                    ];
                }
            }
            return $postOptions;
        }

        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        /**
         * @return \Psr\Http\Message\ResponseInterface
         * @throws \Exception
         */
        private function getGuzzleRequest()
        {
            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedNamespaceInspection */
            /** @var \Psr\Http\Message\ResponseInterface $gRequest */
            $gRequest = null;
            if (method_exists($this->DRIVER, 'request')) {
                if ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_GET) {
                    $gRequest = $this->DRIVER->request('GET', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS);
                } elseif ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_POST) {
                    $gRequest = $this->DRIVER->request('POST', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS);
                } elseif ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_PUT) {
                    $gRequest = $this->DRIVER->request('PUT', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS);
                } elseif ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_DELETE) {
                    $gRequest = $this->DRIVER->request('DELETE', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS);
                } elseif ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_HEAD) {
                    $gRequest = $this->DRIVER->request('HEAD', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS);
                }
            } else {
                throw new \Exception(
                    NETCURL_CURL_CLIENTNAME . " streams for guzzle is probably missing as I can't find the request method in the current class",
                    $this->NETWORK->getExceptionCode('NETCURL_GUZZLESTREAM_MISSING')
                );
            }
            return $gRequest;
        }

        /**
         * @return string
         */
        public function getRawResponse()
        {
            return $this->RESPONSE_RAW;
        }

        /**
         * @param string $url
         * @param array $postData
         * @param int $postMethod
         * @param int $postDataType
         * @return NETCURL_DRIVER_GUZZLEHTTP
         * @throws \Exception
         */
        public function executeNetcurlRequest(
            $url = '',
            $postData = [],
            $postMethod = NETCURL_POST_METHODS::METHOD_GET,
            $postDataType = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET
        ) {
            $this->REQUEST_URL = $url;
            $this->POST_DATA = $postData;
            $this->POST_METHOD = $postMethod;
            $this->POST_DATA_TYPE = $postDataType;

            $this->initializeClass();
            if (is_null($this->DRIVER)) {
                throw new \Exception(
                    $this->ModuleName . " setDriverException: Classes for GuzzleHttp does not exists (DriverIdMissing: " . $this->DRIVER_ID . ")",
                    $this->NETWORK->getExceptionCode('NETCURL_EXTERNAL_DRIVER_MISSING')
                );
            }

            return $this->getGuzzle();
        }

    }
}