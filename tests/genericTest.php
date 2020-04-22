<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\NetUtils;
use TorneLIB\Module\Network\NetWrapper;

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
            (new NetUtils())->getGitTagsByUrl("https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git")
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getGitTagsNetcurlBucket()
    {
        $tags = (new NetUtils())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/");
        static::assertGreaterThan(
            2,
            $tags
        );
    }

    /**
     * @test
     * Get list of tags between two versions.
     */
    public function getGitTagsByVersion()
    {
        $info = (new NetUtils())->getGitTagsByVersion(
            'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
            '6.0.8',
            '6.0.13',
            '>='
        );

        static::assertGreaterThan(
            2,
            count($info)
        );
    }

    /**
     * @test
     * Test version tags from chosen version and get a list with versions higher than current.
     */
    public function getHigherVersions()
    {
        $info = (new NetUtils())->getHigherVersions(
            'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
            '6.0.9'
        );

        static::assertGreaterThan(
            2,
            count($info)
        );
    }

    /**
     * @test
     * Test latest tag with latest release. List should be empty.
     */
    public function getMyVersion()
    {
        $info = (new NetUtils())->getHigherVersions(
            'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
            (new NetWrapper())->getVersion()
        );

        static::assertCount(
            0,
            $info
        );
    }

    /**
     * @test
     */
    public function getVersionTrueFalse()
    {
        static::assertFalse(
            (new NetUtils())->getVersionLatest(
                'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
                '6.0.9'
            )
        );
    }

}
