<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
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
}
