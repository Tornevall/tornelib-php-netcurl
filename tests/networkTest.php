<?php

namespace TorneLIB;

if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require_once( __DIR__ . '/../vendor/autoload.php' );
}
if ( file_exists( __DIR__ . "/../tornelib.php" ) ) {
	// Work with TorneLIBv5
	require_once( __DIR__ . '/../tornelib.php' );
}

use PHPUnit\Framework\TestCase;

ini_set( 'memory_limit', - 1 );    // Free memory limit, some tests requires more memory (like ip-range handling)

class networkTest extends TestCase {

	private $NET;

	function setUp() {
		$this->NET = new MODULE_NETWORK();
	}

	/**
	 * @test
	 */
	function getArpaLocalhost4() {
		$this->assertTrue( $this->NET->getArpaFromIpv4( "127.0.0.1" ) === "1.0.0.127" );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost6() {
		$this->assertTrue( $this->NET->getArpaFromIpv6( "::1" ) === "1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0" );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost4Second() {
		$this->assertTrue( $this->NET->getArpaFromIpv4( "192.168.12.36" ) === "36.12.168.192" );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost6Second() {
		$this->assertTrue( $this->NET->getArpaFromIpv6( "2a01:299:a0:ff:10:128:255:2" ) === "2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2" );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost4Nulled() {
		$this->assertEmpty( $this->NET->getArpaFromIpv4( null ) );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost6Nulled() {
		$this->assertEmpty( $this->NET->getArpaFromIpv6( null ) );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost4String() {
		$this->assertEmpty( $this->NET->getArpaFromIpv4( "fail here" ) );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost6String() {
		$this->assertEmpty( $this->NET->getArpaFromIpv6( "fail here" ) );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost6CorruptString1() {
		$this->assertEmpty( $this->NET->getArpaFromIpv6( "a : b \\" ) );
	}

	/**
	 * @test
	 */
	function getArpaLocalhost6CorruptString2() {
		$badString = "";
		for ( $i = 0; $i < 255; $i ++ ) {
			$badString .= chr( $i );
		}
		$this->assertEmpty( $this->NET->getArpaFromIpv6( $badString ) );
	}

	/**
	 * @test
	 */
	function octetV6() {
		$this->assertTrue( $this->NET->getIpv6FromOctets( "2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2" ) === "2a01:299:a0:ff:10:128:255:2" );
	}

	/**
	 * @test
	 */
	function getArpaAuto4() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "172.16.12.3" ) === "3.12.16.172" );
	}

	/**
	 * @test
	 */
	function getArpaAuto6() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "2a00:1450:400f:802::200e" ) === "e.0.0.2.0.0.0.0.0.0.0.0.0.0.0.0.2.0.8.0.f.0.0.4.0.5.4.1.0.0.a.2" );
	}

	/**
	 * @test
	 */
	function getIpType4() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "172.22.1.83", true ) === TorneLIB_Network_IP_Protocols::PROTOCOL_IPV4 );
	}

	/**
	 * @test
	 */
	function getIpType6() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "2a03:2880:f113:83:face:b00c:0:25de", true ) === TorneLIB_Network_IP_Protocols::PROTOCOL_IPV6 );
	}

	/**
	 * @test
	 */
	function getIpTypeFail() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "This.Aint.An.Address", true ) === TorneLIB_Network_IP_Protocols::PROTOCOL_NONE );
	}

	/**
	 * @test
	 */
	function maskRangeArray24() {
		$this->assertCount( 255, $this->NET->getRangeFromMask( "192.168.1.0/24" ) );
	}

	/**
	 * @test
	 */
	function maskRangeArray16() {
		$this->assertCount( 65535, $this->NET->getRangeFromMask( "192.168.0.0/16" ) );
	}

	/**
	 * @test
	 */
	function maskRange24() {
		$this->assertTrue( $this->NET->isIpInRange( "192.168.1.55", "192.168.1.0/24" ) );
	}

	/**
	 * @test
	 */
	function maskRange24Fail() {
		$this->assertFalse( $this->NET->isIpInRange( "192.168.2.55", "192.168.1.0/24" ) );
	}

	/**
	 * @test
	 */
	function maskRange16() {
		$this->assertTrue( $this->NET->isIpInRange( "192.168.2.55", "192.168.0.0/16" ) );
	}

	/**
	 * @test
	 */
	function maskRange8() {
		$this->assertTrue( $this->NET->isIpInRange( "172.213.9.3", "172.0.0.0/8" ) );
	}

	/**
	 * @test
	 */
	function hostResolveValidationSuccess() {
		$localNetwork = new MODULE_NETWORK();
		$localNetwork->setAlwaysResolveHostvalidation( true );
		$urlData = $localNetwork->getUrlDomain( "http://www.tornevall.net/" );
		$this->assertTrue( $urlData[0] == "www.tornevall.net" );
	}

	/**
	 * @test
	 */
	function hostResolveValidationFail() {
		$localNetwork = new MODULE_NETWORK();
		$localNetwork->setAlwaysResolveHostvalidation( true );
		try {
			$urlData = $localNetwork->getUrlDomain( "http://failing.domain/" );
		} catch (\Exception $e) {
			$this->assertTrue($e->getCode() == NETCURL_EXCEPTIONS::NETCURL_HOSTVALIDATION_FAIL);
		}
	}

	/**
	 * @test
	 */
	function hostValidationNoResolve() {
		$localNetwork = new MODULE_NETWORK();
		$urlData      = $localNetwork->getUrlDomain( "http://failing.domain/" );
		$this->assertTrue( $urlData[0] == "failing.domain" );
	}
}