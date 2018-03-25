<?php

namespace TorneLIB;

require_once (__DIR__ . "/../vendor/autoload.php");

use PHPUnit\Framework\TestCase;
use \TorneLIB\MODULE_SSL;

class sslTest extends TestCase {

	/** @var MODULE_SSL */
	private $SSL;

	function setUp() {
		$this->SSL = new MODULE_SSL();
	}

	/**
	 * @test
	 * @testdox If SSL is available, this will be a positive test
	 */
	public function getCurlSslAvailable() {
		$sslAvailable = MODULE_SSL::getCurlSslAvailable();
		$this->assertCount(0, $sslAvailable);
	}

	/**
	 * @test
	 * @testdox Get a certificate bundle
	 */
	public function getSslCertificate() {
		$this->assertTrue(strlen($this->SSL->getSslCertificateBundle()) > 0);
	}

	/**
	 * @test
	 * @testdox SSL hardening - nothing is allowed except for a correct SSL setup
	 */
	public function strictStream() {
		$sslArray = $this->SSL->getSslStreamContext();
		$this->assertTrue($sslArray['verify_peer'] == true && $sslArray['verify_peer_name'] == true && $sslArray['verify_host'] == true && $sslArray['allow_self_signed'] == false);
	}

	/**
	 * @test
	 * @testdox Make SSL validation sloppy, allow anything
	 */
	public function unStrictStream() {
		$this->SSL->setStrictVerification(false, true);
		$sslArray = $this->SSL->getSslStreamContext();
		$this->assertTrue($sslArray['verify_peer'] == false && $sslArray['verify_peer_name'] == false && $sslArray['verify_host'] == false && $sslArray['allow_self_signed'] == true);
	}

	/**
	 * @test
	 * @testdox Make SSL validation strict but allow self signed certificates
	 */
	public function strictStreamSelfSignedAllowed() {
		$this->SSL->setStrictVerification(true, true);
		$sslArray = $this->SSL->getSslStreamContext();
		$this->assertTrue($sslArray['verify_peer'] == true && $sslArray['verify_peer_name'] == true && $sslArray['verify_host'] == true && $sslArray['allow_self_signed'] == true);
	}

	/**
	 * @test
	 * @testdox Get a generated context stream prepared for the SSL configuration
	 */
	function sslStream() {
		$streamContext = $this->SSL->getSslStream();
		$this->assertTrue(is_resource($streamContext['stream_context']));
	}

}
