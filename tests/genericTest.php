<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\NetUtil;

require_once(__DIR__ . '/../vendor/autoload.php');

class genericTest extends TestCase
{
    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getGitTagsNetcurl()
    {
        static::assertGreaterThan(
            2,
            (new NetUtil())->getGitTagsByUrl("https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git")
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getGitTagsNetcurlBucket()
    {
        $tags = (new NetUtil())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/");
        static::assertGreaterThan(
            2,
            $tags
        );
    }
}
