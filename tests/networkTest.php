<?php

namespace TorneLIB;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}
if (file_exists(__DIR__ . "/../tornelib.php")) {
    // Work with TorneLIBv5
    /** @noinspection PhpIncludeInspection */
    require_once(__DIR__ . '/../tornelib.php');
}

use PHPUnit\Framework\TestCase;

ini_set('memory_limit', -1);    // Free memory limit, some tests requires more memory (like ip-range handling)

class networkTest extends TestCase
{

    /** @var MODULE_NETWORK $NET */
    private $NET;

    /**
     * @var string $bitBucketUrl Bitbucket URL without scheme
     */
    private $bitBucketUrl = 'bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git';

    function setUp()
    {
        error_reporting(E_ALL);
        $this->NET = new MODULE_NETWORK();
    }

    /**
     * @test
     */
    public function getArpaLocalhost4()
    {
        static::assertTrue($this->NET->getArpaFromIpv4("127.0.0.1") === "1.0.0.127");
    }

    /**
     * @test
     */
    public function getArpaLocalhost6()
    {
        static::assertTrue($this->NET->getArpaFromIpv6("::1") === "1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0");
    }

    /**
     * @test
     */
    public function getArpaLocalhost4Second()
    {
        static::assertTrue($this->NET->getArpaFromIpv4("192.168.12.36") === "36.12.168.192");
    }

    /**
     * @test
     */
    public function getArpaLocalhost6Second()
    {
        static::assertTrue($this->NET->getArpaFromIpv6("2a01:299:a0:ff:10:128:255:2") === "2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2");
    }

    /**
     * @test
     */
    public function getArpaLocalhost4Nulled()
    {
        static::assertEmpty($this->NET->getArpaFromIpv4(null));
    }

    /**
     * @test
     */
    public function getArpaLocalhost6Nulled()
    {
        static::assertEmpty($this->NET->getArpaFromIpv6(null));
    }

    /**
     * @test
     */
    public function getArpaLocalhost4String()
    {
        static::assertEmpty($this->NET->getArpaFromIpv4("fail here"));
    }

    /**
     * @test
     */
    public function getArpaLocalhost6String()
    {
        static::assertEmpty($this->NET->getArpaFromIpv6("fail here"));
    }

    /**
     * @test
     */
    public function getArpaLocalhost6CorruptString1()
    {
        static::assertEmpty($this->NET->getArpaFromIpv6("a : b \\"));
    }

    /**
     * @test
     */
    public function getArpaLocalhost6CorruptString2()
    {
        $badString = "";
        for ($i = 0; $i < 255; $i++) {
            $badString .= chr($i);
        }
        static::assertEmpty($this->NET->getArpaFromIpv6($badString));
    }

    /**
     * @test
     */
    public function octetV6()
    {
        static::assertTrue($this->NET->getIpv6FromOctets("2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2") === "2a01:299:a0:ff:10:128:255:2");
    }

    /**
     * @test
     */
    public function getArpaAuto4()
    {
        static::assertTrue($this->NET->getArpaFromAddr("172.16.12.3") === "3.12.16.172");
    }

    /**
     * @test
     */
    public function getArpaAuto6()
    {
        static::assertTrue($this->NET->getArpaFromAddr("2a00:1450:400f:802::200e") === "e.0.0.2.0.0.0.0.0.0.0.0.0.0.0.0.2.0.8.0.f.0.0.4.0.5.4.1.0.0.a.2");
    }

    /**
     * @test
     */
    public function getIpType4()
    {
        static::assertTrue(
            $this->NET->getArpaFromAddr("172.22.1.83", true) === NETCURL_IP_PROTOCOLS::PROTOCOL_IPV4
        );
    }

    /**
     * @test
     */
    public function getIpType6()
    {
        static::assertTrue(
            $this->NET->getArpaFromAddr(
                "2a03:2880:f113:83:face:b00c:0:25de",
                true
            ) === NETCURL_IP_PROTOCOLS::PROTOCOL_IPV6
        );
    }

    /**
     * @test
     */
    public function getIpTypeFail()
    {
        static::assertTrue(
            $this->NET->getArpaFromAddr(
                "This.Aint.An.Address",
                true
            ) === NETCURL_IP_PROTOCOLS::PROTOCOL_NONE
        );
    }

    /**
     * @test
     */
    public function maskRangeArray24()
    {
        static::assertCount(255, $this->NET->getRangeFromMask("192.168.1.0/24"));
    }

    /**
     * @test
     */
    public function maskRangeArray16()
    {
        static::assertCount(65535, $this->NET->getRangeFromMask("192.168.0.0/16"));
    }

    /**
     * @test
     */
    public function maskRange24()
    {
        static::assertTrue($this->NET->isIpInRange("192.168.1.55", "192.168.1.0/24"));
    }

    /**
     * @test
     */
    public function maskRange24Fail()
    {
        static::assertFalse($this->NET->isIpInRange("192.168.2.55", "192.168.1.0/24"));
    }

    /**
     * @test
     */
    public function maskRange16()
    {
        static::assertTrue($this->NET->isIpInRange("192.168.2.55", "192.168.0.0/16"));
    }

    /**
     * @test
     */
    public function maskRange8()
    {
        static::assertTrue($this->NET->isIpInRange("172.213.9.3", "172.0.0.0/8"));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function hostResolveValidationSuccess()
    {
        $localNetwork = new MODULE_NETWORK();
        $localNetwork->setAlwaysResolveHostvalidation(true);
        $urlData = $localNetwork->getUrlDomain("http://www.tornevall.net/");
        static::assertTrue($urlData[0] == "www.tornevall.net");
    }

    /**
     * @test
     */
    public function hostResolveValidationFail()
    {
        $localNetwork = new MODULE_NETWORK();
        $localNetwork->setAlwaysResolveHostvalidation(true);
        try {
            $urlData = $localNetwork->getUrlDomain("http://failing.domain/");
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == NETCURL_EXCEPTIONS::NETCURL_HOSTVALIDATION_FAIL);
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function hostValidationNoResolve()
    {
        $localNetwork = new MODULE_NETWORK();
        $urlData = $localNetwork->getUrlDomain("http://failing.domain/");
        static::assertTrue($urlData[0] == "failing.domain");
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getGitTagsGuest()
    {
        $tags = $this->NET->getGitTagsByUrl("https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git");
        static::assertGreaterThan(1, $tags);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getGitTagsCredentials()
    {
        $tags = $this->NET->getGitTagsByUrl(
            "https://test:test123@bitbucket.tornevall.net/scm/misc/netcurl-test.git"
        );
        static::assertGreaterThan(1, $tags);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGitInfo()
    {
        try {
            // Old way of clearing out misconfigured credentials
            $NetCurl = $this->NET->getGitTagsByUrl(
                "https://userCredentialsBanned@" . $this->bitBucketUrl,
                false,
                false,
                false
            );
            $GuzzleLIB = $this->NET->getGitTagsByUrl("https://github.com/guzzle/guzzle.git");
            static::assertTrue(count($NetCurl) >= 0 && count($GuzzleLIB) >= 0);
        } catch (\Exception $e) {
        }
    }

    /**
     * @test
     */
    public function getGitVersion()
    {
        try {
            // Using Guzzle in this test means that we need to clean up the tags from non numeric content, as they
            // use mixed tags like: v2.x, v3.x, 1.x, etc.
            $GuzzleLIB = $this->NET->getGitTagsByVersion(
                "https://github.com/guzzle/guzzle.git", '2.0', '3.0',
                true,
                true
            );
            $fullSerie = 0;
            $onlyTwo = 0;
            foreach ($GuzzleLIB as $v) {
                $fullSerie++;
                if (preg_match("/^2./", $v)) {
                    $onlyTwo++;
                }
            }
            static::assertTrue($fullSerie === $onlyTwo);
        } catch (\Exception $e) {
        }
    }

    /**
     * @test
     */
    public function getUrlsFromHtml()
    {
        $html = '
		<html>
			<a href="http://test.com/url1">URL 1</a>
			<a href=\'http://test.com/url2\'>URL 2</a>
			<a href= "http://test.com/url3" >URL 3</a>
			<a href= \'http://test.com/url4\' >URL 4</a>
			<img src="http://test.com/img1">IMG 1</a>
			<img src=\'http://test.com/img2\'>IMG 2</a>
			<img src= "http://test.com/img3" >IMG 3</a>
			<img src= \'http://test.com/img4\' >IMG 4</a>

			<a href="http://test.com/durl1">Duplicate URL 1</a>
			<a href=\'http://test.com/durl2\'>Duplicate URL 2</a>
			<a href= "http://test.com/durl3" >Duplicate URL 3</a>
			<a href= \'http://test.com/durl4\' >Duplicate URL 4</a>
			<img src="http://test.com/dimg1">Duplicate IMG 1</a>
			<img src=\'http://test.com/dimg2\'>Duplicate IMG 2</a>
			<img src= "http://test.com/dimg3" >Duplicate IMG 3</a>
			<img src= \'http://test.com/dimg4\' >Duplicate IMG 4</a>
			</html>
		';

        static::assertCount(16, $this->NET->getUrlsFromHtml($html));
    }
}
