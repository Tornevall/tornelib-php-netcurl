<?php
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpComposerExtensionStubsInspection */

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Config\Flag;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Flags;
use TorneLIB\Helpers\Browsers;
use TorneLIB\Helpers\Version;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\MODULE_CURL;
use TorneLIB\Utils\Security;

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

/**
 * Class curlWrapperTest
 */
class curlWrapperTest extends TestCase
{
    /**
     * @test
     * Test initial curl wrapper with predefined http request.
     * @since 6.1.0
     */
    public function initialCurlWrapper()
    {
        try {
            $curlWrapperArgs = new CurlWrapper(
                'https://ipv4.netcurl.org',
                [],
                RequestMethod::GET,
                [
                    'flag1' => 'present',
                    'flag2' => 'available',
                ]
            );

            // Initialize primary curlWrapper to test with.
            $curlWrapper = new CurlWrapper();
        } catch (Exception $e) {
            static::markTestIncomplete(sprintf(
                'Skipped test on exception %s: %s',
                $e->getCode(),
                $e->getMessage()
            ));
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        static::assertTrue(
            (is_object($curlWrapperArgs) &&
                is_object($curlWrapper) &&
                is_object($curlWrapper->getConfig()) &&
                count(Flags::_getAllFlags()) >= 2)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @throws ReflectionException
     * @since 6.1.0
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
     * @since 6.1.0
     */
    public function safeMode()
    {
        $security = new Security();
        // version_compare(PHP_VERSION, '5.4.0', '>=')
        if (PHP_VERSION_ID >= 50400) {
            static::assertFalse($security->getSafeMode());
        } else {
            static::assertTrue($security->getSafeMode());
        }
    }

    /**
     * @test
     * Check secure mode status.
     * @since 6.1.0
     */
    public function secureMode()
    {
        $security = new Security();
        // version_compare(PHP_VERSION, '5.4.0', '>=')
        if (PHP_VERSION_ID >= 50400) {
            static::assertFalse($security->getSecureMode());
        } else {
            static::assertTrue($security->getSecureMode());
        }
    }

    /**
     * @test
     * Check what the Browsers-class are generating.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function browserSet()
    {
        // Making sure output lloks the same in both ends.
        static::assertTrue(
            (bool)preg_match('/^mozilla|^netcurl/i', (new Browsers())->getBrowser()) &&
            (bool)preg_match('/^mozilla|^netcurl/i', (new WrapperConfig())->getOptions()['10018'])
        );
    }

    /**
     * @test
     * Make multiple URL request s.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function basicMultiGet()
    {
        $wrapper = (new CurlWrapper())->setConfig($this->setTestAgent())->request([
            sprintf('https://ipv4.netcurl.org/ip.php?func=%s', __FUNCTION__),
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
        ]);

        /** @noinspection PhpUnitTestsInspection */
        // This test is relatively inactive.
        static::assertTrue(is_object($wrapper));
    }

    /**
     * @return WrapperConfig
     * @since 6.1.0
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
     * @since 6.1.4
     */
    public function basicMultiIdentical()
    {
        // First request: Custom duplicate request (configurable arrays have higher priority in test).
        $wrapperFirst = (new CurlWrapper())->request([
            [
                'url' => 'https://ipv4.netcurl.org/',
                'requestMethod' => RequestMethod::POST,
                'dataType' => DataType::NORMAL,
                'data' => [
                    'dataRequestMethod' => 'FIRST',
                ],
                'headers' => [
                    'XHeaderFirst' => 'yes',
                    'X-Real-IP' => '255.255.255.0',
                    'Client-IP' => '127.0.0.255',
                    'X-Forwarded-For' => '127.255.0.0',
                ],
                'headers_static' => [
                    'HeaderIsForever' => 'only-in-non-multi-curls',
                ],
            ],
            [
                'url' => 'https://ipv4.netcurl.org/',
                'requestMethod' => RequestMethod::POST,
                'dataType' => DataType::NORMAL,
                'data' => [
                    'dataRequestMethod' => 'SECOND',
                ],
                'headers' => [
                    'XHeaderSecond' => 'yes',
                ],
            ],
            [
                'url' => 'https://ipv4.netcurl.org/',
                'requestMethod' => RequestMethod::GET,
                'data' => [
                    'dataRequestMethod' => 'THIRD',
                ],
            ],
        ]);

        // Second request: Single URL request. No extra data.
        $wrapperSecond = (new CurlWrapper())->request([
            'https://ipv4.netcurl.org/',
            'https://ipv4.netcurl.org/',
        ]);

        $bodies = [];
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($parsed = $wrapperFirst->getParsed()) {
            $bodies[] = $parsed;
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($parsed = $wrapperSecond->getParsed()) {
            $bodies[] = $parsed;
        }

        static::assertTrue(
            count($bodies) === 5 &&
            $bodies[0]->REQUEST_METHOD === 'POST' &&
            $bodies[1]->REQUEST_METHOD === 'POST' &&
            $bodies[2]->REQUEST_METHOD === 'GET' &&
            $bodies[3]->REQUEST_METHOD === 'GET' &&
            $bodies[4]->REQUEST_METHOD === 'GET' &&
            $bodies[0]->PARAMS_REQUEST->dataRequestMethod === 'FIRST'
        );
    }

    /**
     * @test
     * Test the curlWrapper constructor with a basic request.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function curlWrapperConstructor()
    {
        $curlRequest = (new CurlWrapper(
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
            [],
            RequestMethod::GET,
            [
                'flag1' => 'present',
                'flag2' => 'available',
            ]
        ));

        $curlRequest->setOptionCurl($curlRequest->getCurlHandle(), CURLOPT_USERAGENT, __FUNCTION__);
        $curlRequest->getCurlRequest();

        /** @noinspection PhpUnitTestsInspection */
        static::assertTrue(is_object($curlRequest));
    }

    /**
     * @test
     * Make a basic get and validate response.
     * @throws ExceptionHandler
     * @since 6.1.0
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
     * Run basic request where netcurl is automatically render "correct" request.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function basicGetHeader()
    {
        $data = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__));

        $contentType = $data->getHeader('content-type');

        static::assertTrue($this->getMatchingJson($contentType, $data->getParsed()));
    }

    /**
     * Protect against faulty redirects from server and find out if response is json.
     * @param $contentType
     * @param $content
     * @return bool
     * @since 6.1.2
     */
    private function getMatchingJson($contentType, $content)
    {
        $return = false;

        if ($contentType === 'text/html; charset=iso-8859-1') {
            // Consider this json if object when getting the above content type.
            // This content type occurs on HTTP 301 Redirect responses and is not
            // caused by this module.
            if (!empty($content) && is_object($content)) {
                $return = true;
            }
        } elseif ((bool)preg_match('/application\/json/', $contentType)) {
            $return = true;
        }

        return $return;
    }

    /**
     * @test
     * Run basic post request with parameters.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function basicPostWithGetData()
    {
        $curlWrapper = new CurlWrapper();
        $data = $curlWrapper
            ->setConfig($this->setTestAgent())
            ->request(
                sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
                ['hello' => 'world'],
                RequestMethod::POST
            )
            ->getParsed();

        if ($this->is300($curlWrapper->getCode())) {
            static::markTestSkipped(sprintf('Server unexpectedly returned %s.', $curlWrapper->getCode()));
            return;
        }

        static::assertTrue(
            (isset($data->PARAMS_REQUEST->hello, $data->PARAMS_GET->func) && $data->PARAMS_GET->func === __FUNCTION__)
        );
    }

    /**
     * Find out whether response is based on 300 redirect. This is not an internal error, but
     * caused randomly as it seems, by remote server for unknown reasons.
     * @param $wrapperCode
     * @return bool
     * @since 6.1.0
     */
    private function is300($wrapperCode)
    {
        $return = false;
        if (is_numeric($wrapperCode) && $wrapperCode >= 300 && $wrapperCode < 400) {
            $return = true;
        }
        return $return;
    }

    /**
     * @test
     * Run a basic get request.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function basicGetWithPost()
    {
        $data = (new CurlWrapper())
            ->setConfig($this->setTestAgent())
            ->request(
                sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
                ['hello' => 'world'],
                RequestMethod::GET
            )
            ->getParsed();

        static::assertNotTrue(isset($data->PARAMS_REQUEST->hello));
    }

    /**
     * @test
     * Run a basic post request.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function basicPost()
    {
        $curlWrapper = new CurlWrapper();
        $data = $curlWrapper
            ->setConfig($this->setTestAgent())
            ->request(
                sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
                ['hello' => 'world'],
                RequestMethod::POST
            )
            ->getParsed();

        if ($this->is300($curlWrapper->getCode())) {
            static::markTestSkipped(sprintf('Server unexpectedly returned %s.', $curlWrapper->getCode()));
            return;
        }

        static::assertTrue(isset($data->PARAMS_POST->hello));
    }

    /**
     * @test
     * Create basic request with a specific user agent.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function basicGetHeaderUserAgent()
    {
        $curlWrapper = new CurlWrapper();
        $curlRequest =
            $curlWrapper
                ->setConfig((new WrapperConfig())->setOptions([CURLOPT_USERAGENT => 'ExternalClientName']))
                ->request(
                    sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__)
                );

        if ($this->is300($curlWrapper->getCode())) {
            static::markTestSkipped(sprintf('Server unexpectedly returned %s.', $curlWrapper->getCode()));
            return;
        }
        static::assertSame($curlRequest->getHeader('content-type'), 'application/json');
    }

    /**
     * @test
     * Ask for multiple urls.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function multiGetHeader()
    {
        $firstMultiUrl = sprintf(
            'https://ipv4.netcurl.org/?func=%s&php=%s',
            __FUNCTION__,
            rawurlencode(PHP_VERSION)
        );
        $secondMultiUrl = sprintf(
            'https://ipv4.netcurl.org/ip.php?func=%s&php=%s',
            __FUNCTION__,
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
     * @since 6.1.0
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
     * @since 6.1.0
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
     * @since 6.1.0
     */
    public function unInitializedCurlWrapperNoConfig()
    {
        try {
            $wrapper = new CurlWrapper();
            $wrapper->getCurlRequest();
        } catch (Exception $e) {
            // curl or netcurl will throw something here.
            static::assertTrue(
                $e->getCode() === Constants::LIB_EMPTY_URL ||
                $e->getCode() === 3
            );
        }
    }

    /**
     * @test
     * Certificate errors like this can obviously render two different kind of errors.
     * @since 6.1.0
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
     * @since 6.1.0
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
     * Tests WrapperConfig and data setup.
     * @noinspection PhpUndefinedMethodInspection
     * @since 6.1.0
     */
    public function setConfigData()
    {
        $config = new WrapperConfig();

        // No need to pre-set any timeout as WrapperConfig makes sure the timeouts are properly set.
        $gTimeout = $config->getTimeout();
        try {
            // Undefined method is what we test.
            $config->getEmptySetting();
        } catch (Exception $e) {
            static::assertTrue(
                $e->getCode() === Constants::LIB_CONFIGWRAPPER_VAR_NOT_SET &&
                (int)$gTimeout['REQUEST'] === 10 &&
                (int)$gTimeout['CONNECT'] === 5
            );
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @since 6.1.2
     */
    public function setStaticHeaders()
    {
        $wrapper = new CurlWrapper();
        $wrapper->setCurlHeader('myHeaderIsStatic', true, true);
        $parsed = $wrapper->request(
            'https://ipv4.netcurl.org'
        )->getParsed();

        $secondParsed = $wrapper->request(
            'https://ipv4.netcurl.org/?secondRequest=1'
        )->getParsed();

        static::assertTrue(
            isset($parsed->HTTP_MYHEADERISSTATIC, $secondParsed->HTTP_MYHEADERISSTATIC)
        );
    }

    /**
     * @test
     * Test timeout configurations.
     * @noinspection PhpUndefinedMethodInspection
     * @since 6.1.0
     */
    public function setMilliTimeout()
    {
        $config = new WrapperConfig();
        $errorCode = 0;
        $config->setTimeout(100, true);
        // No need to pre-set any timeout as WrapperConfig makes sure the timeouts are properly set.
        $gTimeout = $config->getTimeout();
        try {
            // Undefined method is what we test.
            /** @noinspection PhpUndefinedMethodInspection */
            $config->getEmptySetting();
        } catch (Exception $e) {
            // The last request will make WrapperConfig throw an exception as getEmptySetting does not exist
            // in the magics setup. So we'll check the other values from here. Floats are returned regardless
            // of seconds and milliseconds, so we'll cast the values into integers here.
            $errorCode = $e->getCode();
        }
        static::assertTrue(
            $errorCode === Constants::LIB_CONFIGWRAPPER_VAR_NOT_SET &&
            (int)$gTimeout['REQUEST'] === 100 &&
            (int)$gTimeout['CONNECT'] === 50 &&
            (bool)$gTimeout['MILLISEC']
        );
    }

    /**
     * @test
     * Setting proxy the hard way.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function proxyPrimary()
    {
        if (!$this->canProxy()) {
            static::markTestSkipped('Can not perform proxy tests with this client. Skipped.');
            return;
        }

        $wrapper = new CurlWrapper();
        $wrapper->setConfig((new WrapperConfig())->setUserAgent('InTheProxy'));
        $wrapper->setOptionCurl($wrapper->getCurlHandle(), CURLOPT_PROXY, '212.63.208.8:80');
        $wrapper->setOptionCurl($wrapper->getCurlHandle(), CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        $response = $wrapper->request('http://identifier.tornevall.net/?inTheProxy')->getParsed();
        static::assertTrue(
            isset($response->ip) &&
            $response->ip === '212.63.208.8'
        );
    }

    /**
     * @return bool
     * @throws ExceptionHandler
     * @noinspection DuplicatedCode
     * @since 6.1.0
     */
    private function canProxy()
    {
        $return = false;

        $ipList = [
            '212.63.208.',
            '10.1.1.',
        ];

        $wrapperData = (new CurlWrapper())
            ->setConfig((new WrapperConfig())->setUserAgent('ProxyTestAgent'))
            ->request('https://ipv4.netcurl.org')->getParsed();
        if (isset($wrapperData->ip)) {
            foreach ($ipList as $ip) {
                if ((bool)preg_match('/' . $ip . '/', $wrapperData->ip)) {
                    $return = true;
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * @test
     * @since 6.1.0
     * @noinspection PhpDeprecationInspection
     */
    public function getLibraryVersion()
    {
        // module_curl
        /** @noinspection PhpUnhandledExceptionInspection */
        $mCurl = (new MODULE_CURL())->getVersion();
        // real curl
        /** @noinspection PhpUnhandledExceptionInspection */
        $rCurl = (new CurlWrapper())->getVersion();
        static::assertTrue(
            (bool)preg_match('/^6\.1/', $mCurl) &&
            (bool)preg_match('/^6\.1/', $rCurl)
        );
    }

    /**
     * @test
     * Setting proxy the easy way.
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function proxyInternal()
    {
        if (!$this->canProxy()) {
            static::markTestSkipped('Can not perform proxy tests with this client. Skipped.');
            return;
        }

        $wrapperResponse = (new CurlWrapper())->setProxy('212.63.208.8:80')
            ->request('http://identifier.tornevall.net/?inTheProxy')
            ->getParsed();

        static::assertTrue(
            isset($wrapperResponse->ip) &&
            $wrapperResponse->ip === '212.63.208.8'
        );
    }

    /**
     * @test
     */
    public function curlWrapperDefaultMisconfiguredTimeout()
    {
        Flag::setFlag('WRAPPER_DEFAULT_TIMEOUT', 'hello');
        $curlWrapper = new CurlWrapper();
        Flag::deleteFlag('WRAPPER_DEFAULT_TIMEOUT');
        $timeout = $curlWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 5);
    }

    /**
     * @test
     */
    public function curlWrapperDefaultRealTimeout()
    {
        $curlWrapper = new CurlWrapper();
        $curlWrapper->setTimeout(15);
        $timeout = $curlWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 8);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function curlWrapperDefaultZeroTimeout()
    {
        static::expectException(ExceptionHandler::class);
        $curlWrapper = new CurlWrapper();
        $curlWrapper->setTimeout(1, true);
        $curlWrapper->request('https://ipv4.netcurl.org');
    }

    /**
     * @test
     */
    public function stdRequest() {
        $curlWrapper = new CurlWrapper();
        try {
            $curlWrapper->request('https://test.resurs.com');
        } catch (Exception $e) {
            if ($e->getCode() === CURLE_COULDNT_RESOLVE_HOST) {
                static::assertTrue($e->getCode() === CURLE_COULDNT_CONNECT);
                return;
            }
            static::markTestSkipped('Exception is accepted due to the request. Skipped.');
            return;
        }

        // Ignore above if it works. Used for testing LIB_NETCURL_CONNECTION_REFUSED, but for curl.
        // For curl, we get this in a natural way.
        static::assertTrue(true);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function setSigStandard()
    {
        WrapperConfig::setSignature("Sig 1");
        WrapperConfig::setSignature("Sig 2");
        $currentSignature = WrapperConfig::getSignature();
        WrapperConfig::setSignature("Sig 3", false);
        $nextSignature = WrapperConfig::getSignature();
        WrapperConfig::setSignature("Sig 4");
        $overSignature = WrapperConfig::getSignature();
        Flag::setFlag('PROTECT_FIRST_SIGNATURE', true);
        WrapperConfig::setSignature("Sig 5");
        $protectSignature = WrapperConfig::getSignature();
        Flag::deleteFlag('PROTECT_FIRST_SIGNATURE');
        WrapperConfig::setSignature("Sig 6");
        $overWritten = WrapperConfig::getSignature();

        static::assertTrue(
            $currentSignature === $nextSignature &&
            $overSignature === $protectSignature &&
            $protectSignature !== $overWritten
        );
    }
}
