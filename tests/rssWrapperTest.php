<?php
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedClassInspection */

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\IO\Data\Arrays;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\RssWrapper;

class rssWrapperTest extends TestCase
{
    public function testConsumeLaminasRss()
    {
        if (!class_exists('\Laminas\Feed\Reader\Reader')) {
            static::markTestSkipped('Laminas is not present.');
            return;
        }
        try {
            $rssFeed = (new RssWrapper())->request('https://www.tornevalls.se/feed/')->getParsed();
            if (method_exists($rssFeed, 'getTitle')) {
                static::assertNotSame($rssFeed->getTitle(), '');
            } else {
                static::assertTrue(isset($rssFeed[0][0]) && strlen($rssFeed[0][0]) > 5);
            }
        } catch (Exception $e) {
            static::markTestSkipped(sprintf('Non critical exception in %s: %s (%s).', __FUNCTION__, $e->getMessage(), $e->getCode()));
        }
    }

    public function testConsumeByNetWrapperNormalRequest()
    {
        /** @noinspection DuplicatedCode */
        try {
            $rssFeed = (new NetWrapper())->request('https://www.tornevalls.se/feed/', [], RequestMethod::GET, DataType::NORMAL)->getParsed();


            // Test failed for PHP 8: Checking methods requires that it is not null first.
            if (!isset($rssFeed) && !empty($rssFeed) && method_exists($rssFeed, 'getTitle')) {
                static::assertNotSame($rssFeed->getTitle(), '');
            } else {
                $rssFeed = (new Arrays())->objectsIntoArray($rssFeed);
                static::assertTrue(isset($rssFeed[0][0]) && strlen($rssFeed[0][0]) > 5);
            }
        } catch (Exception $e) {
            static::markTestSkipped(sprintf('Non critical exception in %s: %s (%s).', __FUNCTION__, $e->getMessage(), $e->getCode()));
        }
    }

    public function testConsumeByNetWrapperLaminasChoice()
    {
        if (!class_exists('\Laminas\Feed\Reader\Reader')) {
            static::markTestSkipped('Laminas is not present.');
            return;
        }

        /** @noinspection DuplicatedCode */
        try {
            $rssFeed = (new NetWrapper())->request('https://www.tornevalls.se/feed/', [], RequestMethod::GET, DataType::RSS_XML)->getParsed();

            if (method_exists($rssFeed, 'getTitle')) {
                static::assertNotSame($rssFeed->getTitle(), '');
            } else {
                static::assertTrue(isset($rssFeed[0][0]) && strlen($rssFeed[0][0]) > 5);
            }
        } catch (Exception $e) {
            static::markTestSkipped(sprintf('Non critical exception in %s: %s (%s).', __FUNCTION__, $e->getMessage(), $e->getCode()));
        }
    }
}
