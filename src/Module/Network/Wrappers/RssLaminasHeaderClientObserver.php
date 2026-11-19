<?php
/**
 * Copyright © Thomas Tornevall / Tornevall Networks. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace TorneLIB\Module\Network\Wrappers;

use Laminas\Feed\Reader\Http\HeaderAwareClientInterface;

/**
 * Preserve Laminas conditional HTTP request headers while observing status.
 */
class RssLaminasHeaderClientObserver extends RssLaminasClientObserver implements HeaderAwareClientInterface
{
    /**
     * @param string $url
     * @param array $headers
     * @return \Laminas\Feed\Reader\Http\ResponseInterface
     */
    public function get($url, array $headers = [])
    {
        return $this->observe($this->client->get($url, $headers));
    }
}
