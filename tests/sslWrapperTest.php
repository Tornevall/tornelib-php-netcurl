<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Flags;
use TorneLIB\Helpers\SSL;

require_once(__DIR__ . '/../vendor/autoload.php');

class sslWrapperTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function noSslWrappers()
    {
        Flags::setFlag('NETCURL_NOSSL_TEST');
        try {
            /** @var SSL $SSL */
            $SSL = new SSL();
            $SSL->getSslCapabilities();
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() === 500);
        }

        // Clean up global flags.
        Flags::clearAllFlags();
    }

    /**
     * @test
     * @throws Exception
     */
    public function sslWrappers()
    {
        static::assertTrue((new SSL())->getSslCapabilities());
    }

    /**
     * @test
     * @throws Exception
     */
    public function strictValidation()
    {
        $sslAction = new SSL();
        $sslAction->setStrictVerification(false);
        $verifyPeerChange = $sslAction->getSecurityLevelChanges();

        /*
         * Tests includes:
         *  - Disable verify_peer (assert false).
         *  - Check the default content of allow_self_signed (assert).
         *  - Set own context and validates content.
         *  - Set own context and validates content by keyed call to getContext()
         *  - Verify that security level changes are "logged".
         */
        static::assertTrue(
            !(bool)$sslAction->getContext()['verify_peer'] &&
            (bool)!(new SSL())->getContext()['allow_self_signed'] &&
            (new SSL())->setContext('passphrase', 'simple_phrase')->getContext()['passphrase'] &&
            (new SSL())->setContext('passphrase', 'simple_phrase')->getContext('passphrase') === 'simple_phrase' &&
            is_array($verifyPeerChange) &&
            count($verifyPeerChange) === 1
        );
    }

    /**
     * @test
     * @testdox Make sure the streamcontext are created properly.
     */
    public function getPreparedSslContext()
    {
        static::assertTrue(is_resource((new SSL())->getSslStreamContext()['stream_context']));
    }
}
