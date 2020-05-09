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
    }

    /**
     * @test
     */
    public function consumeByNetWrapperNormalRequest()
    {
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
    }

    /**
     * @test
     */
    public function consumeByNetWrapperLaminasChoice()
    {
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
    }
}
