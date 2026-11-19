<?php
/**
 * Copyright © Thomas Tornevall / Tornevall Networks. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace TorneLIB\Module\Network\Wrappers;

use Laminas\Feed\Reader\Http\ClientInterface;
use Laminas\Feed\Reader\Http\ResponseInterface;

/**
 * Observe the response returned by an existing Laminas Feed HTTP client.
 *
 * This decorator does not fetch or parse RSS itself. It preserves the
 * configured Laminas client and records actual HTTP response metadata.
 */
class RssLaminasClientObserver implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var int|null
     */
    private $statusCode;

    /**
     * @var string|null
     */
    private $responseBody;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $url
     * @return ResponseInterface
     */
    public function get($url)
    {
        return $this->observe($this->client->get($url));
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function observe($response)
    {
        if ($response instanceof ResponseInterface) {
            $this->statusCode = $response->getStatusCode();
            $this->responseBody = $response->getBody();
        }

        return $response;
    }

    /**
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
