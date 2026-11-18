<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Config\WrapperCurlOpt;
use TorneLIB\Module\Config\WrapperSSL;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Deterministic TLS verification regression coverage for WrapperConfig.
 */
class wrapperConfigSslVerificationTest extends TestCase
{
    /**
     * @param WrapperConfig $config
     * @param int $expectedPeer
     * @param int $expectedHost
     */
    private function assertVerificationOptions(WrapperConfig $config, $expectedPeer, $expectedHost)
    {
        $options = $config->getOptions();

        static::assertSame($expectedPeer, $options[WrapperCurlOpt::NETCURL_CURLOPT_SSL_VERIFYPEER]);
        static::assertSame($expectedHost, $options[WrapperCurlOpt::NETCURL_CURLOPT_SSL_VERIFYHOST]);
    }

    public function testStrictDefaultsAndRepeatedPolicyChanges()
    {
        $config = new WrapperConfig();
        $ssl = $config->getSsl();

        $this->assertVerificationOptions($config, 1, 2);

        $ssl->setStrictVerification(false);
        $this->assertVerificationOptions($config, 0, 0);
        $this->assertVerificationOptions($config, 0, 0);

        $ssl->setStrictVerification(true);
        $this->assertVerificationOptions($config, 1, 2);
        $this->assertVerificationOptions($config, 1, 2);

        $ssl->setStrictVerification(false);
        $this->assertVerificationOptions($config, 0, 0);

        $ssl->setStrictVerification(true);
        $this->assertVerificationOptions($config, 1, 2);
    }

    public function testPeerAndHostSettingsAreIndependent()
    {
        $config = new WrapperConfig();
        $ssl = $config->getSsl();

        $ssl->setContext('verify_peer', false);
        $this->assertVerificationOptions($config, 0, 2);

        $ssl->setContext('verify_peer', true);
        $ssl->setContext('verify_host', false);
        $this->assertVerificationOptions($config, 1, 0);

        $ssl->setContext('verify_host', true);
        $this->assertVerificationOptions($config, 1, 2);
    }

    public function testReplacingSslConfigurationRestoresStrictDefaults()
    {
        $config = new WrapperConfig();

        $config->getSsl()->setStrictVerification(false);
        $this->assertVerificationOptions($config, 0, 0);

        $config->setSsl(new WrapperSSL());
        $this->assertVerificationOptions($config, 1, 2);
    }

    public function testExplicitCurlOptionsSurviveUntilSslPolicyChanges()
    {
        $config = new WrapperConfig();

        // Legacy callers can directly configure cURL options.
        $config->setOption(WrapperCurlOpt::NETCURL_CURLOPT_SSL_VERIFYPEER, 0);
        $config->setOption(WrapperCurlOpt::NETCURL_CURLOPT_SSL_VERIFYHOST, 0);
        $this->assertVerificationOptions($config, 0, 0);

        // Changing the SSL policy takes precedence and can restore strict values.
        $ssl = $config->getSsl();
        $ssl->setStrictVerification(false);
        $this->assertVerificationOptions($config, 0, 0);

        $ssl->setStrictVerification(true);
        $this->assertVerificationOptions($config, 1, 2);

        // Later explicit overrides are still available until another policy change.
        $config->setOption(WrapperCurlOpt::NETCURL_CURLOPT_SSL_VERIFYHOST, 0);
        $this->assertVerificationOptions($config, 1, 0);
    }
}
