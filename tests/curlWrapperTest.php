<?php

require_once(NETCURL_BASE . '/../vendor/autoload.php');

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

    /**
     * @return bool
     * @throws ExceptionHandler
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
                if (preg_match('/' . $ip . '/', $wrapperData->ip)) {
                    $return = true;
                    break;
                }
            }
        }

        return $return;
    }

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
     * @throws ReflectionException
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
            // curl or netcurl will throw something here.
            static::assertTrue(
                $e->getCode() == Constants::LIB_EMPTY_URL ||
                $e->getCode() === 3
            );
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
                (int)$gTimeout['REQUEST'] === 8 &&
                (int)$gTimeout['CONNECT'] === 4
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

    /**
     * @test
     * Setting proxy the hard way.
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
     * @test
     * Setting proxy the easy way.
     * @throws ExceptionHandler
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
}
