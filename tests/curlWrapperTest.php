<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Flags;
use TorneLIB\Helpers\Browsers;
use TorneLIB\Helpers\Version;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;

define('LIB_ERROR_HTTP', true);

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

/**
 * Class curlWrapperTest
 * @version 6.1.0
 */
class curlWrapperTest extends TestCase
{
    private $curlWrapper;
    private $rEcomPipeU = 'tornevall';
    private $rEcomPipeP = '2suyqJRXyd8YBGxTz42xr7g1tCWW6M2R';
    private $rEcomHost = 'https://omnitest.resurs.com';

    /**
     * @test
     * Test initial curl wrapper with predefined http request.
     */
    public function initialCurlWrapper()
    {
        try {
            $curlWrapperArgs = new CurlWrapper(
                'https://ipv4.netcurl.org',
                [],
                \TorneLIB\Model\Type\requestMethod::METHOD_GET,
                [
                    'flag1' => 'present',
                    'flag2' => 'available',
                ]
            );

            // Initialize primary curlwrapper to test with.
            $this->curlWrapper = new CurlWrapper();
        } catch (\Exception $e) {
            static::markTestIncomplete(sprintf(
                'Skipped test on exception %s: %s',
                $e->getCode(),
                $e->getMessage()
            ));
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        static::assertTrue(
            (
                is_object($curlWrapperArgs) &&
                is_object($this->curlWrapper) &&
                is_object($this->curlWrapper->getConfig()) &&
                count(Flags::_getAllFlags()) === 2
            ) ? true : false
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getVersion()
    {
        static::assertTrue(
            version_compare(
                (new CurlWrapper())->getVersion(),
                '6.1',
                '>='
            )
        );
    }

    /**
     * @test
     */
    public function safeMode()
    {
        $security = new \TorneLIB\Utils\Security();
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            static::assertFalse($security->getSafeMode());
        } else {
            static::assertTrue($security->getSafeMode());
        }
    }

    /**
     * @test
     * Check secure mode status.
     */
    public function secureMode()
    {
        $security = new \TorneLIB\Utils\Security();
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            static::assertFalse($security->getSecureMode());
        } else {
            static::assertTrue($security->getSecureMode());
        }
    }

    /**
     * @test
     * Check what the Browsers-class are generating.
     */
    public function browserSet()
    {
        // Making sure output lloks the same in both ends.
        static::assertTrue(
            preg_match('/^mozilla|^netcurl/i', (new Browsers())->getBrowser()) ? true : false &&
                preg_match('/^mozilla|^netcurl/i', (new WrapperConfig())->getOptions()['10018'])
        );
    }

    /**
     * @return WrapperConfig
     */
    private function setTestAgent()
    {
        return (new WrapperConfig())->setUserAgent(
            sprintf('netcurl-%s', NETCURL_VERSION)
        );
    }

    /**
     * @test
     * Make multiple URL request s.
     * @throws ExceptionHandler
     */
    public function basicMultiGet()
    {
        $wrapper = (new CurlWrapper())->setConfig($this->setTestAgent())->request([
            sprintf('https://ipv4.netcurl.org/ip.php?func=%s', __FUNCTION__),
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
        ]);

        // This test is relatively inactive.
        static::assertTrue(is_object($wrapper));
    }

    /**
     * @test
     * Test the curlwrapper constructor with a basic request.
     * @throws ExceptionHandler
     */
    public function curlWrapperConstructor()
    {
        /** @var CurlWrapper $curlRequest */
        $curlRequest = (new CurlWrapper(
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
            [],
            \TorneLIB\Model\Type\requestMethod::METHOD_GET,
            [
                'flag1' => 'present',
                'flag2' => 'available',
            ]
        ));

        $curlRequest->setOptionCurl($curlRequest->getCurlHandle(), CURLOPT_USERAGENT, __FUNCTION__);
        $curlRequest->getCurlRequest();

        static::assertTrue(is_object($curlRequest));
    }

    /**
     * @test
     * Make a basic get and validate response.
     * @throws ExceptionHandler
     */
    public function basicGet()
    {
        $wrapper = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__));

        $parsed = $wrapper->getParsed();

        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @test
     * Make a TLS 1.0 request.
     * @throws ExceptionHandler
     */
    public function basicGetLowTLS()
    {
        $tlsResponse = (new CurlWrapper())->
        setConfig((new WrapperConfig())
            ->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_0)
            ->setUserAgent(sprintf('netcurl-%s', NETCURL_VERSION)))
            ->request(
                sprintf('https://ipv4.netcurl.org/?func=%s',
                    __FUNCTION__
                )
            )->getParsed();


        if (isset($tlsResponse->ip)) {
            static::assertTrue(
                filter_var($tlsResponse->ip, FILTER_VALIDATE_IP) ? true : false &&
                    $tlsResponse->SSL->SSL_PROTOCOL === 'TLSv1'
            );
        }
    }

    /**
     * @test
     * Make a TLS 1.1 request.
     * @throws ExceptionHandler
     */
    public function basicGetTLS11()
    {
        $tlsResponse = (new CurlWrapper())->
        setConfig(
            (new WrapperConfig())
                ->setUserAgent(sprintf('netcurl-%s', NETCURL_VERSION))
                ->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1)
        )->request(
            sprintf('https://ipv4.netcurl.org/?func=%s',
                __FUNCTION__
            )
        )->getParsed();

        if (isset($tlsResponse->ip)) {
            static::assertTrue(
                filter_var($tlsResponse->ip, FILTER_VALIDATE_IP) ? true : false &&
                    $tlsResponse->SSL->SSL_PROTOCOL === 'TLSv1.1'
            );
        }
    }

    /**
     * @test
     * Make a TLS 1.3 request (if available).
     */
    public function basicGetTLS13()
    {
        if (defined('CURL_SSLVERSION_TLSv1_3') && version_compare(PHP_VERSION, '5.6', '>=')) {
            try {
                $tlsResponse = (new CurlWrapper())->
                setConfig(
                    (new WrapperConfig())
                        ->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3)
                        ->setUserAgent(sprintf('netcurl-%s', NETCURL_VERSION)))
                    ->request(
                        sprintf('https://ipv4.netcurl.org/?func=%s',
                            __FUNCTION__
                        )
                    )->getParsed();

                if (isset($tlsResponse->ip)) {
                    static::assertTrue(
                        filter_var($tlsResponse->ip, FILTER_VALIDATE_IP) ? true : false &&
                            $tlsResponse->SSL->SSL_PROTOCOL === 'TLSv1.3'
                    );
                }
            } catch (Exception $e) {
                // Getting connect errors here may indicate that the netcurl server is missing TLS 1.3 support.
                // TLS 1.3 is supported from Apache 2.4.37
                // Also be aware of the fact that not all PHP releases support it.
                if ($e->getCode() === CURLE_SSL_CONNECT_ERROR) {
                    // 14094410
                    static::markTestSkipped($e->getMessage());
                }
            }
        } else {
            if (version_compare(PHP_VERSION, '5.6', '>=')) {
                static::markTestSkipped('TLSv1.3 problems: Your platform is too old to even bother.');
            } else {
                static::markTestSkipped('TLSv1.3 is not available on this platform.');
            }
        }
    }

    /**
     * @test
     * Run basic request where netcurl is automatically render "correct" request.
     * @throws ExceptionHandler
     */
    public function basicGetHeader()
    {
        $data = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__));

        static::assertTrue($data->getHeader('content-type') === 'application/json');
    }

    /**
     * @test
     * Run basic post request with parameters.
     * @throws ExceptionHandler
     */
    public function basicPostWithGetData()
    {
        $data = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(
                sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
                ['hello' => 'world'],
                \TorneLIB\Model\Type\requestMethod::METHOD_POST)
            ->getParsed();

        static::assertTrue(
            isset($data->PARAMS_REQUEST->hello) &&
            isset($data->PARAMS_GET->func) &&
            $data->PARAMS_GET->func === __FUNCTION__
        );
    }

    /**
     * @test
     * Run a basic get request.
     * @throws ExceptionHandler
     */
    public function basicGetWithPost()
    {
        $data = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(
                sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
                ['hello' => 'world'],
                \TorneLIB\Model\Type\requestMethod::METHOD_GET)
            ->getParsed();

        static::assertTrue(!isset($data->PARAMS_REQUEST->hello));
    }

    /**
     * @test
     * Run a basic post request.
     * @throws ExceptionHandler
     */
    public function basicPost()
    {
        $data = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(
                sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
                ['hello' => 'world'],
                \TorneLIB\Model\Type\requestMethod::METHOD_POST)
            ->getParsed();

        static::assertTrue(isset($data->PARAMS_POST->hello));
    }

    /**
     * @test
     * Create basic request with a specific user agent.
     * @throws ExceptionHandler
     */
    public function basicGetHeaderUserAgent()
    {
        $curlRequest =
            (new CurlWrapper())
                ->setConfig((new WrapperConfig())->setOptions([CURLOPT_USERAGENT => 'ExternalClientName']))
                ->request(
                    sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__)
                );

        static::assertTrue($curlRequest->getHeader('content-type') === 'application/json');
    }

    /**
     * @test
     * Ask for multiple urls.
     * @throws ExceptionHandler
     */
    public function multiGetHeader()
    {
        $firstMultiUrl = sprintf(
            'https://ipv4.netcurl.org/?func=%s&php=%s',
            __FUNCTION__,
            rawurlencode(PHP_VERSION)
        );
        $secondMultiUrl = sprintf(
            'https://ipv4.netcurl.org/ip.php?func=%s&php=%s', __FUNCTION__,
            rawurlencode(PHP_VERSION)
        );
        $data = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(
                [
                    $firstMultiUrl,
                    $secondMultiUrl,
                ]
            );

        static::assertTrue(
            $data->getHeader('content-type', $firstMultiUrl) === 'application/json' &&
            strlen($data->getHeader(null, $secondMultiUrl)) > 1
        );
    }

    /**
     * @test
     * Initialize empty curlwrapper - set url after init and request an uninitialized wrapper. Expected result
     * is self initialized wrapper of curl.
     * @throws ExceptionHandler
     */
    public function unInitializedCurlWrapperByConfig()
    {
        $wrapper = (new CurlWrapper());
        $wrapper->getConfig()->setRequestUrl(
            sprintf(
                'https://ipv4.netcurl.org/?func=%s&php=%s',
                __FUNCTION__,
                rawurlencode(PHP_VERSION)
            )
        );
        $wrapper->setOptionCurl($wrapper->getCurlHandle(), CURLOPT_USERAGENT, __FUNCTION__);
        $parsed = $wrapper->getCurlRequest()->getParsed();
        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @test
     * Initialize netcurl without predefined url.
     * @throws ExceptionHandler
     */
    public function unInitializedCurlWrapperMinorConfig()
    {
        $wrapper = new CurlWrapper();
        $wrapper->setConfig($this->setTestAgent());
        $wrapper->getConfig()->setRequestUrl(
            sprintf(
                'https://ipv4.netcurl.org/?func=%s&php=%s',
                __FUNCTION__,
                rawurlencode(PHP_VERSION)
            )
        );
        $parsed = $wrapper->getCurlRequest()->getParsed();
        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @test
     * Lowest initializer level, where nothing can be initiated since there is no defined url.
     */
    public function unInitializedCurlWrapperNoConfig()
    {
        try {
            $wrapper = new CurlWrapper();
            $wrapper->getCurlRequest();
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == Constants::LIB_EMPTY_URL);
        }
    }

    /**
     * @test
     * Certificate errors like this can obviously render two different kind of errors.
     */
    public function sslCurlePeerCert51()
    {
        try {
            (new CurlWrapper('https://dev-ssl-mismatch.tornevall.nu'))->getCurlRequest();
        } catch (Exception $e) {
            static::assertTrue(
                $e->getCode() === CURLE_SSL_PEER_CERTIFICATE ||
                $e->getCode() === CURLE_SSL_CONNECT_ERROR
            );
        }
    }

    /**
     * @test
     * Certificate errors like this can obviously render two different kind of errors.
     */
    public function sslCurleCacert60()
    {
        try {
            (new CurlWrapper('https://dev-ssl-self.tornevall.nu'))->getCurlRequest();
        } catch (Exception $e) {
            static::assertTrue(
                $e->getCode() === CURLE_SSL_CACERT ||
                $e->getCode() === CURLE_SSL_CONNECT_ERROR
            );
        }
    }

    /**
     * @test
     * Testing a simple "real world" rest request
     * @throws ExceptionHandler
     */
    public function getRestWithAuth()
    {
        $curlWrapper = new CurlWrapper();

        try {
            $wrapperRequest = $curlWrapper->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP)->request(
                sprintf('%s/callbacks', $this->rEcomHost)
            );

            $parsed = $wrapperRequest->getParsed();
            static::assertTrue(is_array($parsed));
        } catch (Exception $e) {
            // 35 when ssl version set to 4.
            static::markTestIncomplete(
                sprintf(
                    '%s exception %s: %s',
                    __FUNCTION__,
                    $e->getCode(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @test
     * Real world rest request with specific body errors.
     * @throws ExceptionHandler
     */
    public function getThrowablesByBody()
    {
        $curlWrapper = new CurlWrapper();

        try {
            $curlWrapper->setAuthentication(
                $this->rEcomPipeU, $this->rEcomPipeP)->request(
                sprintf(
                    '%s/payment/nonExistingReference',
                    $this->rEcomHost
                )
            );
        } catch (ExceptionHandler $e) {
            $body = $curlWrapper->getParsed();
            /** @var CurlWrapper $curlWrapperException */
            $curlWrapperException = $e->getExtendException()->getParsed();

            // ExceptionHandler will give more information without the need to put the wrapperRequest
            // outside the try/catch, nor initialize it from there. In this test we will be able to
            // trigger an error and the continue requesting body/parsed content with the help from exception.
            //
            // Alternatively it is also possible to do the opposite of this comment - initialize
            // the curlwrapper, and then continue request for the information from there.
            static::assertTrue(
                (int)$e->getCode() === 404 &&
                (int)$body->code === 404 &&
                !empty($curlWrapperException->message)
            );
        }
    }

    /**
     * @test
     * Tests WrapperConfig and data setup.
     */
    public function setConfigData()
    {
        $config = new WrapperConfig();

        // No need to pre-set any timeout as WrapperConfig makes sure the timeouts are properly set.
        $gTimeout = $config->getTimeout();
        try {
            $config->getEmptySetting();
        } catch (ExceptionHandler $e) {
            static::assertTrue(
                $e->getCode() === Constants::LIB_CONFIGWRAPPER_VAR_NOT_SET &&
                (int)$gTimeout['REQUEST'] === 6 &&
                (int)$gTimeout['CONNECT'] === 3
            );
        }
    }

    /**
     * @test
     * Test timeout configurations.
     */
    public function setMilliTimeout()
    {
        $config = new WrapperConfig();

        $config->setTimeout(100, true);
        // No need to pre-set any timeout as WrapperConfig makes sure the timeouts are properly set.
        $gTimeout = $config->getTimeout();
        try {
            $config->getEmptySetting();
        } catch (ExceptionHandler $e) {
            // The last request will make WrapperConfig throw an exception as getEmptySetting does not exist
            // in the magics setup. So we'll check the other values from here. Floats are returned regardless
            // of seconds and milliseconds, so we'll cast the values into integers here.
            static::assertTrue(
                $e->getCode() === Constants::LIB_CONFIGWRAPPER_VAR_NOT_SET &&
                (int)$gTimeout['REQUEST'] === 100 &&
                (int)$gTimeout['CONNECT'] === 50 &&
                (bool)$gTimeout['MILLISEC']
            );
        }
    }
}
