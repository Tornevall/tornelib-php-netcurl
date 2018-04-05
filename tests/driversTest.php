<?php

namespace TorneLIB;

if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require_once( __DIR__ . '/../vendor/autoload.php' );
}

require_once(__DIR__ . "/testurls.php");

use PHPUnit\Framework\TestCase;

class driversTest extends TestCase {

	/** @var NETCURL_DRIVER_CONTROLLER $DRIVERCLASS */
	private $DRIVERCLASS;

	/** @var MODULE_CURL $CURL */
	private $CURL;

	function setUp() {
		$this->DRIVERCLASS = new NETCURL_DRIVER_CONTROLLER();
		$this->CURL = new MODULE_CURL();
	}

	/**
	 * @test
	 */
	function getSystemWideDrivers() {
		static::assertTrue( count( $this->DRIVERCLASS->getSystemWideDrivers() ) > 1 ? true : false );
	}

	/**
	 * @test
	 */
	function getDisabledFunctions() {
		static::assertTrue( is_array( $this->DRIVERCLASS->getDisabledFunctions() ) );
	}

	/**
	 * @test
	 */
	function getIsDisabled() {
		$UPPERCASE = $this->DRIVERCLASS->getIsDisabled( 'CURL_INIT, CURL_EXEC' );
		$simple    = $this->DRIVERCLASS->getIsDisabled( 'curl_init' );
		$array     = $this->DRIVERCLASS->getIsDisabled( array( 'curl_init', 'curl_exec' ) );
		if ( $UPPERCASE && $simple && $array ) {
			static::assertTrue( $UPPERCASE && $simple && $array );
		} else {
			static::markTestSkipped( "Vital functions that should trigger this test is not disabled." );
		}
	}

	/**
	 * @test
	 */
	function getStaticCurl() {
		static::assertTrue( NETCURL_DRIVER_CONTROLLER::getCurl() );
	}

	/**
	 * @test
	 */
	function getDriverAvailable() {
		static::assertTrue( $this->DRIVERCLASS->getIsDriver( NETCURL_NETWORK_DRIVERS::DRIVER_INTERNAL ) );
	}

	/**
	 * @test
	 */
	function getGuzzleDriver() {
		if ( ! $this->DRIVERCLASS->getIsDriver( NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ) ) {
			static::markTestSkipped( "Guzzle is unavailable for this test" );

			return;
		}
		static::assertTrue( is_object( $this->DRIVERCLASS->getDriver( NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ) ) );
	}

	/**
	 * @test
	 */
	function getDriverGuzzle() {
		if ($this->DRIVERCLASS->getIsDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP)) {
			$this->CURL->setDriver( NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP );
			$returnedDriver = $this->CURL->getDriver();
			static::assertStringEndsWith( "GUZZLEHTTP", get_class( $returnedDriver ) );
		} else {
			static::markTestSkipped("Can not test guzzle without guzzle");
		}
	}

	/**
	 * @test
	 */
	function allDriversDisabled() {
		$this->CURL->setDriver( NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP );
	}

}
