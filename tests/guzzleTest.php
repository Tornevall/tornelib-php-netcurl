<?php

namespace TorneLIB;

require_once (__DIR__ . "/../vendor/autoload.php");

use PHPUnit\Framework\TestCase;
use \TorneLIB\MODULE_SSL;

class sslTest extends TestCase {
	private $CURL;

	function setUp() {
		$this->CURL = new MODULE_CURL();
	}

	/**
	 * @param bool $useStream
	 *
	 * @return bool
	 */
	private function hasGuzzle( $useStream = false ) {
		try {
			if ( ! $useStream ) {
				return $this->CURL->setDriver( NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP );
			} else {
				return $this->CURL->setDriver( NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM );
			}
		} catch (\Exception $e) {
			$this->markTestSkipped( "Can not test guzzle driver without guzzle (".$e->getMessage().")" );
		}
	}

	/**
	 * @test
	 */
	function enableGuzzle() {
		if ( $this->hasGuzzle() ) {
			$info = $this->CURL->doPost( "https://" . \TESTURLS::getUrlTests() . "?o=json&getjson=true&var1=HasVar1", array( 'var2' => 'HasPostVar1' ) );
			$this->CURL->getExternalDriverResponse();
			$parsed = $this->CURL->getParsedResponse( $info );
			$this->assertTrue( $parsed->methods->_REQUEST->var1 === "HasVar1" );
		} else {
			$this->markTestSkipped( "Can not test guzzle driver without guzzle" );
		}
	}

	/**
	 * @test
	 */
	function enableGuzzleStream() {
		if ( $this->hasGuzzle( true ) ) {
			$info = $this->CURL->doPost( "https://" . \TESTURLS::getUrlTests() . "?o=json&getjson=true&getVar=true", array(
				'var1'    => 'HasVar1',
				'postVar' => "true"
			) );
			$this->CURL->getExternalDriverResponse();
			$parsed = $this->CURL->getParsedResponse( $info );
			$this->assertTrue( $parsed->methods->_REQUEST->var1 === "HasVar1" );
		} else {
			$this->markTestSkipped( "Can not test guzzle driver without guzzle" );
		}
	}

	/**
	 * @test
	 */
	function enableGuzzleStreamJson() {
		if ( $this->hasGuzzle( true ) ) {
			$info = $this->CURL->doPost( "https://" . \TESTURLS::getUrlTests() . "?o=json&getjson=true&getVar=true", array(
				'var1'    => 'HasVar1',
				'postVar' => "true",
				'asJson'  => 'true'
			), CURL_POST_AS::POST_AS_JSON );
			$this->CURL->getExternalDriverResponse();
			$parsed = $this->CURL->getParsedResponse( $info );
			$this->assertTrue( $parsed->methods->_REQUEST->var1 === "HasVar1" );
		} else {
			$this->markTestSkipped( "Can not test guzzle driver without guzzle" );
		}
	}

	/**
	 * @test
	 */
	function enableGuzzleWsdl() {
		if ( $this->hasGuzzle() ) {
			// Currently, this one will fail over to SimpleSoap
			$info = $this->CURL->doGet( "http://" . \TESTURLS::getUrlSoap() );
			$this->assertTrue( is_object( $info ) );
		} else {
			$this->markTestSkipped( "Can not test guzzle driver without guzzle" );
		}
	}

	/**
	 * @test
	 */
	function enableGuzzleErrors() {
		if ( $this->hasGuzzle() ) {
			try {
				$info = $this->CURL->doPost( \TESTURLS::getUrlTests() . "&o=json&getjson=true", array( 'var1' => 'HasVar1' ) );
			} catch ( \Exception $wrapError ) {
				$this->assertTrue( $wrapError->getCode() == 404 );
			}
		} else {
			$this->markTestSkipped( "Can not test guzzle driver without guzzle" );
		}
	}
}