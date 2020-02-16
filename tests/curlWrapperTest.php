<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Flags;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;

define('LIB_ERROR_HTTP', true);

require_once(__DIR__ . '/../vendor/autoload.php');

class curlWrapperTest extends TestCase
{
	private $curlWrapper;

	protected function setUp()
	{
		parent::setUp();
	}

	/**
	 * @test
	 * @testdox Test the primary wrapper controller.
	 */
	public function majorWrapperControl()
	{
		$netWrap = new NetWrapper();
		static::assertTrue(count($netWrap->getWrappers()) ? true : false);
	}

	/**
	 * @test
	 */
	public function curlWrapper()
	{
		try {
			$curlWrapperArgs = new CurlWrapper(
				'https://identifier.tornevall.net',
				[],
				\TorneLIB\Model\Type\postMethod::METHOD_GET,
				[
					'flag1' => 'present',
					'flag2' => 'available'
				]
			);

			// Initialize primary curlwrapper to test with.
			$this->curlWrapper = new CurlWrapper();

		} catch (\Exception $e) {
			echo $e->getCode();
		}

		static::assertTrue(
			(
				is_object($curlWrapperArgs) &&
				is_object($this->curlWrapper) &&
				is_object($this->curlWrapper->getConfig()) &&
				count(Flags::getAllFlags() === 2)
			) ? true : false
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
}
