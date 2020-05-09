<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\RssWrapper;

class rssWrapperTest extends TestCase
{
    /**
     * @test
     */
    public function consumeLaminasRss()
    {
        try {

            $rssFeed = (new RssWrapper())->request('https://www.tornevalls.se/feed/')->getParsed();
            if (method_exists($rssFeed, 'getTitle')) {
                static::assertTrue($rssFeed->getTitle() !== '');
            } else {
                static::assertTrue(
                    isset($rssFeed[0]) &&
                    isset($rssFeed[0][0]) &&
                    strlen($rssFeed[0][0]) > 5
                );
            }
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Non critical exception in %s: %s (%s).',
                    __FUNCTION__,
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }

    /**
     * @test
     */
    public function consumeByNetWrapperNormalRequest()
    {
        try {
            $rssFeed = (new NetWrapper())
                ->request('https://www.tornevalls.se/feed/',
                    [],
                    requestMethod::METHOD_GET, dataType::NORMAL
                )->getParsed();

            if (method_exists($rssFeed, 'getTitle')) {
                static::assertTrue($rssFeed->getTitle() !== '');
            } else {
                static::assertTrue(
                    isset($rssFeed[0]) &&
                    isset($rssFeed[0][0]) &&
                    strlen($rssFeed[0][0]) > 5
                );
            }
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Non critical exception in %s: %s (%s).',
                    __FUNCTION__,
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }

    /**
     * @test
     */
    public function consumeByNetWrapperLaminasChoice()
    {
        try {
            $rssFeed = (new NetWrapper())
                ->request('https://www.tornevalls.se/feed/',
                    [],
                    requestMethod::METHOD_GET,
                    dataType::RSS_XML
                )->getParsed();

            if (method_exists($rssFeed, 'getTitle')) {
                static::assertTrue($rssFeed->getTitle() !== '');
            } else {
                static::assertTrue(
                    isset($rssFeed[0]) &&
                    isset($rssFeed[0][0]) &&
                    strlen($rssFeed[0][0]) > 5
                );
            }
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Non critical exception in %s: %s (%s).',
                    __FUNCTION__,
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }
}
