<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\SSL;
use TorneLIB\Flags;

require_once(__DIR__ . '/../vendor/autoload.php');

class sslWrapperTest extends TestCase
{

	/**
	 * @test
	 * @throws Exception
	 */
	public function sslWrappers()
	{
		$SSL = new SSL();
		static::assertTrue(is_object($SSL->getSslCapabilities()));
	}

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
	}
}
