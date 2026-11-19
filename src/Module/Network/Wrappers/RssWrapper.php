<?php
/**
 * Copyright © Thomas Tornevall / Tornevall Networks. All rights reserved.
 * See LICENSE.md for license details.
 */

// Inspections should be ignored here as this is a depending-environment-based class.
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpSingleStatementWithBracesInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection NotOptimalIfConditionsInspection */

namespace TorneLIB\Module\Network\Wrappers;

use Exception;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\Version;
use TorneLIB\Model\Interfaces\WrapperInterface;
use TorneLIB\Model\Type\AuthType;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Utils\Generic;

// If 8 or higher, don't bother.
if (PHP_VERSION_ID < 80000) {
    try {
        Version::getRequiredVersion();
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

/**
 * Class RssWrapper
 * @package TorneLIB\Module\Network\Wrappers
 * @link https://docs.laminas.dev/laminas-feed/consuming-rss/
 */
class RssWrapper implements WrapperInterface
{
    /**
     * @var string
     * @since 6.1.0
     */
    private $requestResponseRaw;

    /**
     * @var
     * @since 6.1.0
     */
    private $requestResponse;

    /**
     * HTTP status from the most recent transport request, or null when
     * parsing content that did not originate from an HTTP request.
     *
     * @var int|null
     */
    private $responseCode;

    /**
     * @var WrapperConfig
     */
    private $CONFIG;

    /**
     * RssWrapper constructor.
     * @since 6.1.0
     */
    public function __construct()
    {
        $this->CONFIG = new WrapperConfig();
        $this->CONFIG->setCurrentWrapper(__CLASS__);
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
     * @param WrapperConfig $config
     * @return RssWrapper
     * @since 6.1.0
     */
    public function setConfig($config)
    {
        $this->CONFIG = $this->getInheritedConfig($config);

        return $this;
    }

    /**
     * @param $config
     * @return mixed
     * @since 6.1.0
     */
    private function getInheritedConfig($config)
    {
        $config->setCurrentWrapper($this->CONFIG->getCurrentWrapper());

        return $config;
    }

    /**
     * @param $username
     * @param $password
     * @param int $authType
     * @return RssWrapper
     * @since 6.1.0
     */
    public function setAuthentication($username, $password, $authType = AuthType::BASIC)
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
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->requestResponseRaw;
    }

    /**
     * @inheritDoc
     */
    public function getParsed($string = '')
    {
        if (!empty($string) && empty($this->requestResponse) && class_exists('\Laminas\Feed\Reader\Reader')) {
            $this->requestResponse = \Laminas\Feed\Reader\Reader::importString($string);
        }
        return $this->requestResponse;
    }

    /**
     * Return the HTTP response code supplied by the underlying transport.
     * Parser-only imports have no HTTP status and return null.
     *
     * @return int|null
     */
    public function getCode()
    {
        return $this->responseCode;
    }

    /**
     * @inheritDoc
     * @return string
     * @throws ExceptionHandler
     * @throws \ReflectionException
     */
    public function getVersion()
    {
        return isset($this->version) && !empty($this->version) ?
            $this->version : (new Generic())->getVersionByAny(__DIR__, 3, WrapperConfig::class);
    }

    /**
     * @inheritDoc
     * @throws ExceptionHandler
     */
    public function request(
        $url,
        $data = [],
        $method = RequestMethod::GET,
        $dataType = DataType::NORMAL
    ) {
        $this->requestResponseRaw = null;
        $this->requestResponse = null;
        $this->responseCode = null;

        if (class_exists('Laminas\\Feed\\Reader\\Reader')) {
            // Keep Laminas in charge of HTTP, RSS/Atom parsing, extensions
            // and its existing caching behavior whenever a client is ready.
            if (interface_exists('Laminas\\Feed\\Reader\\Http\\ClientInterface')) {
                $client = null;
                try {
                    $client = \Laminas\Feed\Reader\Reader::getHttpClient();
                } catch (\Error $error) {
                    if (class_exists('Laminas\\Http\\Client')) {
                        throw $error;
                    }
                    // Without laminas-http or a configured Reader client,
                    // use NetCurl for the download and Reader for parsing.
                }

                if ($client !== null) {
                    $observer = $client instanceof \Laminas\Feed\Reader\Http\HeaderAwareClientInterface ?
                        new RssLaminasHeaderClientObserver($client) :
                        new RssLaminasClientObserver($client);

                    \Laminas\Feed\Reader\Reader::setHttpClient($observer);
                    try {
                        $this->requestResponse = \Laminas\Feed\Reader\Reader::import($url);
                    } finally {
                        // A Reader cache hit may perform no HTTP request.
                        // In that case the status must remain unknown.
                        $this->responseCode = $observer->getStatusCode();
                        $this->requestResponseRaw = $observer->getResponseBody();
                        \Laminas\Feed\Reader\Reader::setHttpClient($client);
                    }

                    return $this;
                }
            } elseif (class_exists('Laminas\\Http\\Client')) {
                // Compatibility for older Reader integrations without the
                // observable HTTP client interface: no guessed status.
                $this->requestResponse = \Laminas\Feed\Reader\Reader::import($url);

                return $this;
            }
        }

        // Fallback only: NetCurl downloads when Laminas has no HTTP client.
        // Laminas Reader still parses the feed when that package is present.
        $transport = new NetWrapper();
        $transport->setConfig(clone $this->CONFIG);

        try {
            $naturalRequest = $transport->request(
                $url,
                [],
                RequestMethod::GET,
                DataType::NORMAL
            );
        } catch (ExceptionHandler $exception) {
            try {
                $code = $transport->getCode();
                if (is_numeric($code) && $code >= 100 && $code <= 599) {
                    $this->responseCode = (int)$code;
                }
            } catch (Exception $ignored) {
                // Network failures without HTTP responses have no status.
            }

            throw $exception;
        }

        $this->responseCode = $naturalRequest->getCode();
        $this->requestResponseRaw = $naturalRequest->getBody();

        if (class_exists('Laminas\\Feed\\Reader\\Reader')) {
            $this->requestResponse = \Laminas\Feed\Reader\Reader::importString($this->requestResponseRaw);
        } else {
            $this->requestResponse = $naturalRequest->getParsed();
        }

        return $this;
    }
}
