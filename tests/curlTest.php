<?php

namespace TorneLIB;

if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require_once( __DIR__ . '/../vendor/autoload.php' );
}
if ( file_exists( __DIR__ . "/../tornelib.php" ) ) {
	// Work with TorneLIBv5
	require_once( __DIR__ . '/../tornelib.php' );
}
require_once( __DIR__ . '/testurls.php' );

use PHPUnit\Framework\TestCase;

ini_set( 'memory_limit', - 1 );    // Free memory limit, some tests requires more memory (like ip-range handling)

class curlTest extends TestCase {
	private $StartErrorReporting;

	/** @var MODULE_NETWORK */
	private $NETWORK;
	/** @var MODULE_CURL */
	private $CURL;
	private $CurlVersion = null;

	/**
	 * @var string $bitBucketUrl Bitbucket URL without scheme
	 */
	private $bitBucketUrl = 'bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git';

	//function tearDown() {}
	function setUp() {
		//$this->setDebug(true);
		$this->StartErrorReporting = error_reporting();
		$this->NETWORK             = new MODULE_NETWORK();
		$this->CURL                = new MODULE_CURL();
		$this->CURL->setUserAgent( "PHPUNIT" );

		if ( function_exists( 'curl_version' ) ) {
			$CurlVersionRequest = curl_version();
			$this->CurlVersion  = $CurlVersionRequest['version'];
		}

		/*
		 * Enable test mode
		 */
		$this->CURL->setTestEnabled();
		$this->CURL->setSslUnverified( false );
	}

	function tearDown() {
		// DebugData collects stats about the curled session.
		// $debugData = $this->CURL->getDebugData();
	}

	/**
	 * iproute2 ifconfig
	 * @return mixed
	 */
	private function getIpListByIpRoute() {
		// Don't fetch 127.0.0.1
		exec( "ip addr|grep \"inet \"|sed 's/\// /'|awk '{print $2}'|grep -v ^127", $returnedExecResponse );

		return $returnedExecResponse;
	}

	private function pemDefault() {
		$this->CURL->setFlag( '_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION', false );
		$this->CURL->setSslVerify( true );
	}

	private function setDebug( $setActive = false ) {
		if ( ! $setActive ) {
			error_reporting( E_ALL );
		} else {
			error_reporting( $this->StartErrorReporting );
		}
	}

	private function simpleGet() {
		return $this->CURL->doGet( \TESTURLS::getUrlSimple() );
	}

	/**
	 * Make sure we always get a protocol
	 *
	 * @param string $protocol
	 *
	 * @return string
	 */
	private function getProtocol( $protocol = 'http' ) {
		if ( empty( $protocol ) ) {
			$protocol = "http";
		}

		return $protocol;
	}

	private function urlGet( $parameters = '', $protocol = "http", $indexFile = 'index.php' ) {
		$theUrl = $this->getProtocol( $protocol ) . "://" . \TESTURLS::getUrlTests() . $indexFile . "?" . $parameters;

		return $this->CURL->doGet( $theUrl );
	}

	private function urlPost( $parameters = array(), $protocol = "http", $indexFile = 'index.php' ) {
		$theUrl = $this->getProtocol( $protocol ) . "://" . \TESTURLS::getUrlTests() . $indexFile;

		return $this->CURL->doPost( $theUrl, $parameters );
	}

	private function hasBody( $container ) {
		if ( is_array( $container ) && isset( $container['body'] ) ) {
			return true;
		}
		if ( is_object( $container ) ) {
			if ( is_string( $container->getResponseBody() ) ) {
				return true;
			}
		}

		return false;
	}

	private function getBody( $container ) {
		if ( is_object( $container ) ) {
			return $container->getResponseBody();
		} else {
			return $this->CURL->getResponseBody();
		}

		return "";
	}

	private function getParsed( $container ) {
		if ( $this->hasBody( $container ) ) {
			if ( is_object( $container ) ) {
				return $container->getParsedResponse();
			}

			return $container['parsed'];
		}

		return null;
	}

	/*function testSimpleGetProxy() {
		$this->pemDefault();
		exec( "service tor status", $ubuntuService );
		$serviceFound = false;
		foreach ( $ubuntuService as $row ) {
			// Unsafe control
			if ( preg_match( "/loaded: loaded/i", $row ) ) {
				$serviceFound = true;
			}
		}
		if ( $serviceFound ) {
			$this->CURL->setProxy( "127.0.0.1:9050", CURLPROXY_SOCKS5 );
			$container = $this->simpleGet();
			$ipType    = $this->NET->getArpaFromAddr( $this->CURL->getResponseBody( $container ), true );
			$this->assertTrue( $ipType > 0 );

			return;
		}
		$this->markTestSkipped( "I can't test this simpleGetProxy since there are no tor service installed" );
	}*/

	/*	function testSimpleGetWsdlProxy() {
			$this->pemDefault();
			exec( "service tor status", $ubuntuService );
			$serviceFound = false;
			foreach ( $ubuntuService as $row ) {
				// Unsafe control
				if ( preg_match( "/loaded: loaded/i", $row ) ) {
					$serviceFound = true;
				}
			}
			if ( $serviceFound ) {
				$this->CURL->setProxy( "127.0.0.1:9050", CURLPROXY_SOCKS5 );
				$container = $this->getBody($this->CURL->doGet("https://" . $this->Urls['soap']));
				return;
			}
			$this->markTestSkipped( "I can't test this simpleGetProxy since there are no tor service installed" );
		}*/


	/**
	 * @test
	 * @testdox Runs a simple test to see if there is a container as it should
	 */
	function simpleGetUrl() {
		$this->pemDefault();
		$container = $this->simpleGet();
		$this->assertTrue( $this->hasBody( $container ) );
	}

	/**
	 * @test
	 * @testdox Fetch a response and immediately pick up the parsed response, from the internally stored last response
	 */
	function getParsedSelf() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$ParsedResponse = $this->CURL->getParsedResponse();
		$this->assertTrue( is_object( $ParsedResponse ) );
	}

	/**
	 * @test
	 * @testdox Make a direct call to the curl library
	 */
	function quickInitParsed() {
		$tempCurl = new MODULE_CURL( "https://identifier.tornevall.net/?json" );
		$this->assertTrue( is_object( $tempCurl->getParsedResponse() ) );
	}

	/**
	 * @test
	 * @testdox Make a direct call to the curl library and get the response code
	 */
	function quickInitResponseCode() {
		$tempCurl = new MODULE_CURL( "https://identifier.tornevall.net/?json" );
		$this->assertTrue( $tempCurl->getResponseCode() == 200 );
	}

	/**
	 * @test
	 * @testdox Make a direct call to the curl library and get the content of the body
	 */
	function quickInitResponseBody() {
		$tempCurl = new MODULE_CURL( "https://identifier.tornevall.net/?json" );
		// Some content must exists in the body
		$this->assertTrue( strlen( $tempCurl->getResponseBody() ) >= 10 );
	}

	/**
	 * @test
	 * @testdox Fetch a response and immediately pick up the parsed response, from own content
	 */
	function getParsedFromResponse() {
		$this->pemDefault();
		$container      = $this->urlGet( "ssl&bool&o=json&method=get" );
		$ParsedResponse = $this->CURL->getParsedResponse( $container );
		$this->assertTrue( is_object( $ParsedResponse ) );
	}

	/**
	 * @test
	 * @testdox Request a specific value from a parsed response
	 */
	function getParsedValue() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$pRes      = $this->CURL->getParsedResponse();
		$ValueFrom = $this->CURL->getParsedValue( 'methods' );
		$this->assertTrue( is_object( $ValueFrom->_REQUEST ) );
	}

	/**
	 * @test
	 * @testdox Request a nested value from a parsed response
	 */
	function getParsedSubValue() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$ValueFrom = $this->CURL->getParsedValue( array( 'nesting', 'subarr4', 'child4' ) );
		$this->assertTrue( count( $ValueFrom ) === 3 );
	}

	/**
	 * @test
	 * @testdox Request a value by sending wrong value into the parser (crash test)
	 */
	function getParsedSubValueNoArray() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$ValueFrom = $this->CURL->getParsedValue( new \stdClass() );
		$this->assertTrue( empty( $ValueFrom ) );
	}

	/**
	 * @test
	 * @testdox Request a value that does not exist in a parsed response (Receive an exception)
	 */
	function getParsedSubValueFail() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$ExpectFailure = false;
		try {
			$this->CURL->getParsedValue( array( 'nesting', 'subarrfail' ) );
		} catch ( \Exception $parseException ) {
			$ExpectFailure = true;
		}
		$this->assertTrue( $ExpectFailure );
	}

	/**
	 * @test
	 * @testdox Test if a web request has a valid body
	 */
	function getValidBody() {
		$this->pemDefault();
		$container = $this->simpleGet();
		$testBody  = $this->getBody( $container );
		$this->assertTrue( ! empty( $testBody ) );
	}

	/**
	 * @test
	 * @testdox Receive a standard 200 code
	 */
	function getSimple200() {
		$this->pemDefault();
		$this->simpleGet();
		$this->assertTrue( $this->CURL->getResponseCode() == 200 );
	}

	/**
	 * @test
	 * @testdox Test SSL based web request
	 */
	function getSslUrl() {
		$this->pemDefault();
		$container = $this->urlGet( "ssl&bool", "https" );
		$testBody  = $this->getBody( $container );
		$this->assertTrue( $this->getBody( $container ) && ! empty( $testBody ) );
	}

	/**
	 * @test
	 * @testdox Get exception on self signed certifications (we get error code 60)
	 */
	function getSslSelfSignedException() {
		$this->pemDefault();
		try {
			$this->CURL->doGet( \TESTURLS::getUrlSelfSigned() );
		} catch ( \Exception $e ) {
			// CURLE_PEER_FAILED_VERIFICATION = 51
			// CURLE_SSL_CACERT = 60
			$this->assertTrue( $e->getCode() == 60 || $e->getCode() == 51 || $e->getCode() == 500 || $e->getCode() === TORNELIB_NETCURL_EXCEPTIONS::NETCURL_SETSSLVERIFY_UNVERIFIED_NOT_SET, $e->getCode() );
		}
	}

	/**
	 * @test
	 * @testdox Get exception on mismatching certificates (host != certifcate host)
	 */
	function sslMismatching() {
		$this->pemDefault();
		try {
			$this->CURL->doGet( \TESTURLS::getUrlSelfSigned() );
		} catch ( \Exception $e ) {
			// CURLE_PEER_FAILED_VERIFICATION = 51
			// CURLE_SSL_CACERT = 60
			$this->assertTrue( $e->getCode() == 60 || $e->getCode() == 51 || $e->getCode() == 500 || $e->getCode() === TORNELIB_NETCURL_EXCEPTIONS::NETCURL_SETSSLVERIFY_UNVERIFIED_NOT_SET );
		}
	}

	/**
	 * @test
	 */
	function sslSelfSignedIgnore() {
		try {
			$this->CURL->setStrictFallback( true );
			$this->CURL->setSslVerify( true, true );
			$container = $this->CURL->getParsedResponse( $this->CURL->doGet( \TESTURLS::getUrlSelfSigned() . "/tests/tornevall_network/index.php?o=json&bool" ) );
			if ( is_object( $container ) ) {
				$this->assertTrue( isset( $container->methods ) );
			}
		} catch ( \Exception $e ) {
			$this->markTestSkipped( "Got exception " . $e->getCode() . ": " . $e->getMessage() );
		}
	}

	/**
	 * @test
	 * @testdox Test that initially allows unverified ssl certificates should make netcurl to first call the url in a correct way and then, if this fails, make a quite risky failover into unverified mode - silently.
	 */
	function sslSelfSignedUnverifyOnRun() {
		$this->pemDefault();
		try {
			$this->CURL->setSslVerify( false );
			$container = $this->CURL->getParsedResponse( $this->CURL->doGet( \TESTURLS::getUrlSelfSigned() . "/tests/tornevall_network/index.php?o=json&bool" ) );
			// The hasErrors function should return at least one error here
			if ( is_object( $container ) && ! $this->CURL->hasErrors() ) {
				$this->assertTrue( isset( $container->methods ) );
			}
		} catch ( \Exception $e ) {
			$this->markTestSkipped( "Got exception " . $e->getCode() . ": " . $e->getMessage() );
		}
	}

	/**
	 * @test
	 * @testdox Test parsed json response
	 */
	function getJson() {
		$this->pemDefault();
		$container = $this->urlGet( "ssl&bool&o=json&method=get" );
		$this->assertTrue( is_object( $this->CURL->getParsedResponse()->methods->_GET ) );
	}

	/**
	 * @test
	 * @testdox Check if we can parse a serialized response
	 */
	function getSerialize() {
		$this->pemDefault();
		$container = $this->urlGet( "ssl&bool&o=serialize&method=get" );
		$this->assertTrue( is_array( $this->CURL->getParsedResponse()['methods']['_GET'] ) );
	}

	/**
	 * @test
	 * @testdox Test if XML/Serializer are parsed correctly
	 */
	function getXmlSerializer() {
		$this->pemDefault();
		// XML_Serializer
		$container = $this->getParsed( $this->urlGet( "ssl&bool&o=xml&method=get" ) );
		$this->assertTrue( isset( $container->using ) && is_object( $container->using ) && $container->using['0'] == "XML/Serializer" );
	}

	/**
	 * @test
	 * @testdox Test if SimpleXml are parsed correctly
	 */
	function getSimpleXml() {
		$this->pemDefault();
		// SimpleXMLElement
		$container = $this->getParsed( $this->urlGet( "ssl&bool&o=xml&method=get&using=SimpleXMLElement" ) );
		$this->assertTrue( isset( $container->using ) && is_object( $container->using ) && $container->using == "SimpleXMLElement" );
	}

	/**
	 * @test
	 * @testdox Test if a html response are converted to a proper array
	 */
	function getSimpleDom() {
		$this->pemDefault();
		$this->CURL->setParseHtml( true );
		try {
			$container = $this->getParsed( $this->urlGet( "ssl&bool&o=xml&method=get&using=SimpleXMLElement", null, "simple.html" ) );
		} catch ( \Exception $e ) {

		}
		// ByNodes, ByClosestTag, ById
		$this->assertTrue( isset( $container['ById'] ) && count( $container['ById'] ) > 0 );
	}


	/***************
	 *  SSL TESTS  *
	 **************/

	/**
	 * @test
	 * @testdox SSL Certificates at custom location. Expected Result: Successful lookup with verified peer
	 */
	function sslCertLocation() {
		$successfulVerification = false;
		try {
			$this->CURL->setSslPemLocations( array( __DIR__ . "/ca-certificates.crt" ) );
			$this->getParsed( $this->urlGet( "ssl&bool&o=json", "https" ) );
			$successfulVerification = true;
		} catch ( \Exception $e ) {
		}
		$this->assertTrue( $successfulVerification );
	}

	/**
	 * @test
	 */
	function setInternalPemLocation() {
		$this->assertTrue( $this->CURL->setSslPemLocations( array( __DIR__ . "/ca-certificates.crt" ) ) );
	}

	/**
	 * @test
	 */
	function setInternalPemLocationBadFormat() {
		try {
			$this->CURL->setSslPemLocations( array( __DIR__ . "/" ) );
		} catch ( \Exception $e ) {
			$this->assertTrue( $e->getCode() == NETCURL_EXCEPTIONS::NETCURL_PEMLOCATIONDATA_FORMAT_ERROR );
		}
	}

	/**
	 * @test
	 * @throws \Exception
	 */
	function unExistentCertificateBundle() {
		$this->CURL->setFlag( 'OVERRIDE_CERTIFICATE_BUNDLE', '/failCertBundle' );
		$this->CURL->setTrustedSslBundles( true );
		try {
			$this->getParsed( $this->urlGet( "ssl&bool&o=json", "https" ) );
		} catch ( \Exception $e ) {
			$this->assertTrue( $e->getCode() == CURLE_SSL_CACERT_BADFILE );
		}
	}

	/**
	 * @test
	 * @testdox SSL Certificates are missing and certificate location is mismatching. Expected Result: Failing the url call
	 */
	function failingSsl() {
		$successfulVerification = true;
		try {
			$this->CURL->setSslVerify( true );
			$this->CURL->setStrictFallback( false );
			$this->CURL->doGet( \TESTURLS::getUrlMismatching() );
		} catch ( \Exception $e ) {
			$successfulVerification = false;
		}
		$this->assertFalse( $successfulVerification );
	}

	/**
	 * @test
	 * @testdox Test the customized ip address
	 */
	function customIpAddrSimple() {
		$this->pemDefault();
		$returnedExecResponse = $this->getIpListByIpRoute();
		// Probably a bad shortcut for some systems, but it works for us in tests
		if ( ! empty( $returnedExecResponse ) && is_array( $returnedExecResponse ) ) {
			$NETWORK = new MODULE_NETWORK();
			$ipArray = array();
			foreach ( $returnedExecResponse as $ip ) {
				// Making sure this test is running safely with non locals only
				if ( ! in_array( $ip, $ipArray ) && $NETWORK->getArpaFromAddr( $ip, true ) > 0 && ! preg_match( "/^10\./", $ip ) && ! preg_match( "/^172\./", $ip ) && ! preg_match( "/^192\./", $ip ) ) {
					$ipArray[] = $ip;
				}
			}
			$this->CURL->IpAddr = $ipArray;
			$CurlJson           = $this->CURL->doGet( \TESTURLS::getUrlSimpleJson() );
			$this->assertNotEmpty( $this->CURL->getParsedResponse()->ip );
		}
	}

	/**
	 * @test
	 * @testdox Test custom ip address setup (if more than one ip is set on the interface)
	 */
	function customIpAddrAllString() {
		$this->pemDefault();
		$ipArray              = array();
		$responses            = array();
		$returnedExecResponse = $this->getIpListByIpRoute();
		if ( ! empty( $returnedExecResponse ) && is_array( $returnedExecResponse ) ) {
			$NETWORK = new MODULE_NETWORK();
			foreach ( $returnedExecResponse as $ip ) {
				// Making sure this test is running safely with non locals only
				if ( ! in_array( $ip, $ipArray ) && $NETWORK->getArpaFromAddr( $ip, true ) > 0 && ! preg_match( "/^10\./", $ip ) && ! preg_match( "/^172\./", $ip ) && ! preg_match( "/^192\./", $ip ) ) {
					$ipArray[] = $ip;
				}
			}
			if ( is_array( $ipArray ) && count( $ipArray ) > 1 ) {
				foreach ( $ipArray as $ip ) {
					$this->CURL->IpAddr = $ip;
					try {
						$CurlJson = $this->CURL->doGet( \TESTURLS::getUrlSimpleJson() );
					} catch ( \Exception $e ) {

					}
					if ( isset( $this->CURL->getParsedResponse()->ip ) && $this->NETWORK->getArpaFromAddr( $this->CURL->getParsedResponse()->ip, true ) > 0 ) {
						$responses[ $ip ] = $this->CURL->getParsedResponse()->ip;
					}
				}
			} else {
				$this->markTestSkipped( "ip address array is too short to be tested (" . print_R( $ipArray, true ) . ")" );
			}
		}
		$this->assertTrue( count( $responses ) === count( $ipArray ) );
	}

	/**
	 * @test
	 * @testdox Run in default mode, when follows are enabled
	 */
	function followRedirectEnabled() {
		$this->pemDefault();
		$redirectResponse = $this->CURL->doGet( "http://developer.tornevall.net/tests/tornevall_network/redirect.php?run" );
		$redirectedUrls   = $this->CURL->getRedirectedUrls();
		$this->assertTrue( intval( $this->CURL->getResponseCode( $redirectResponse ) ) >= 300 && intval( $this->CURL->getResponseCode( $redirectResponse ) ) <= 350 && count( $redirectedUrls ) );
	}

	/**
	 * @test
	 * @testdox Run with redirect follows disabled
	 */
	function followRedirectDisabled() {
		$this->pemDefault();
		$this->CURL->setEnforceFollowLocation( false );
		$redirectResponse = $this->CURL->doGet( "http://developer.tornevall.net/tests/tornevall_network/redirect.php?run" );
		$redirectedUrls   = $this->CURL->getRedirectedUrls();
		$this->assertTrue( $this->CURL->getResponseCode( $redirectResponse ) >= 300 && $this->CURL->getResponseCode( $redirectResponse ) <= 350 && ! preg_match( "/rerun/i", $this->CURL->getResponseBody( $redirectResponse ) ) && count( $redirectedUrls ) );
	}

	/**
	 * @test
	 */
	function followRedirectManualDisable() {
		$this->pemDefault();
		$this->CURL->setEnforceFollowLocation( false );
		$redirectResponse = $this->CURL->doGet( "http://developer.tornevall.net/tests/tornevall_network/redirect.php?run" );
		$redirectedUrls   = $this->CURL->getRedirectedUrls();
		$this->assertTrue( $this->CURL->getResponseCode( $redirectResponse ) >= 300 && $this->CURL->getResponseCode( $redirectResponse ) <= 350 && ! preg_match( "/rerun/i", $this->CURL->getResponseBody( $redirectResponse ) ) && count( $redirectedUrls ) );
	}

	/**
	 * @test
	 * @testdox Tests the overriding function setEnforceFollowLocation and the setCurlOpt-overrider. The expected result is to have setEnforceFollowLocation to be top prioritized over setCurlOpt here.
	 */
	function followRedirectManualEnableWithSetCurlOptEnforcingToFalse() {
		$this->pemDefault();
		$this->CURL->setEnforceFollowLocation( true );
		$this->CURL->setCurlOpt( CURLOPT_FOLLOWLOCATION, false );  // This is the doer since there are internal protection against the above enforcer
		$redirectResponse = $this->CURL->doGet( "http://developer.tornevall.net/tests/tornevall_network/redirect.php?run" );
		$redirectedUrls   = $this->CURL->getRedirectedUrls();
		$this->assertTrue( $this->CURL->getResponseCode( $redirectResponse ) >= 300 && $this->CURL->getResponseCode( $redirectResponse ) <= 350 && count( $redirectedUrls ) );
	}

	/**
	 * @test
	 * @testdox Test SoapClient by making a standard doGet()
	 */
	function wsdlSoapClient() {
		$assertThis = true;
		try {
			$this->CURL->setUserAgent( " +UnitSoapAgent" );
			$this->CURL->doGet( "http://" . \TESTURLS::getUrlSoap() );
		} catch ( \Exception $e ) {
			$assertThis = false;
		}
		$this->assertTrue( $assertThis );
	}

	/**
	 * @test
	 * @testdox Test Soap by internal controllers
	 */
	function hasSoap() {
		$this->assertTrue( $this->CURL->hasSoap() );
	}

	/**
	 * @test
	 */
	function throwableHttpCodes() {
		$this->pemDefault();
		$this->CURL->setThrowableHttpCodes();
		try {
			$this->CURL->doGet( "https://developer.tornevall.net/tests/tornevall_network/http.php?code=503" );
		} catch ( \Exception $e ) {
			$this->assertTrue( $e->getCode() == 503 );

			return;
		}
		$this->markTestSkipped( "No throwables was set up" );
	}

	/**
	 * @test
	 */
	function failUrl() {
		try {
			$this->CURL->doGet( "http://abc" . sha1( microtime( true ) ) );
		} catch ( \Exception $e ) {
			$errorMessage = $e->getMessage();
			$this->assertTrue( ( preg_match( "/maximum tries/", $errorMessage ) ? true : false ) );
		}
	}

	/**
	 * @test
	 */
	public function setCurlOpt() {
		$oldCurl = $this->CURL->getCurlOpt();
		$this->CURL->setCurlOpt( array( CURLOPT_CONNECTTIMEOUT => 10 ) );
		$newCurl = $this->CURL->getCurlOpt();
		$this->assertTrue( $oldCurl[ CURLOPT_CONNECTTIMEOUT ] != $newCurl[ CURLOPT_CONNECTTIMEOUT ] );
	}

	/**
	 * @test
	 */
	public function getCurlOpt() {
		$newCurl = $this->CURL->getCurlOptByKeys();
		$this->assertTrue( isset( $newCurl['CURLOPT_CONNECTTIMEOUT'] ) );
	}

	/**
	 * @test
	 */
	function unsetFlag() {
		$first = $this->CURL->setFlag( "CHAIN", true );
		$this->CURL->unsetFlag( "CHAIN" );
		$second = $this->CURL->hasFlag( "CHAIN" );
		$this->assertTrue( $first && ! $second );
	}

	/**
	 * @test
	 */
	function chainGet() {
		$this->CURL->setFlag( "CHAIN" );
		$this->assertTrue( method_exists( $this->CURL->doGet( \TESTURLS::getUrlSimpleJson() ), 'getParsedResponse' ) );
		$this->CURL->unsetFlag( "CHAIN" );
	}

	/**
	 * @test
	 */
	function tlagEmptyKey() {
		try {
			$this->CURL->setFlag();
		} catch ( \Exception $setFlagException ) {
			$this->assertTrue( $setFlagException->getCode() > 0 );
		}
	}

	/**
	 * @test
	 */
	function chainByInit() {
		$Chainer = new MODULE_CURL( null, null, null, array( "CHAIN" ) );
		$this->assertTrue( is_object( $Chainer->doGet( \TESTURLS::getUrlSimpleJson() )->getParsedResponse() ) );
	}

	/**
	 * @test
	 */
	function chainGetFail() {
		$this->CURL->unsetFlag( "CHAIN" );
		$this->assertFalse( method_exists( $this->CURL->doGet( \TESTURLS::getUrlSimpleJson() ), 'getParsedResponse' ) );
	}

	/**
	 * @test
	 */
	function getGitInfo() {
		try {
			$NetCurl              = $this->NETWORK->getGitTagsByUrl( "https://userCredentialsBanned@" . $this->bitBucketUrl );
			$GuzzleLIB            = $this->NETWORK->getGitTagsByUrl( "https://github.com/guzzle/guzzle.git" );
			$GuzzleLIBNonNumerics = $this->NETWORK->getGitTagsByUrl( "https://github.com/guzzle/guzzle.git", true, true );
			$this->assertTrue( count( $NetCurl ) >= 0 && count( $GuzzleLIB ) >= 0 );
		} catch ( \Exception $e ) {

		}
	}

	/**
	 * @test
	 */
	function getGitIsTooOld() {
		// curl module for netcurl will probably always be lower than the netcurl-version, so this is a good way of testing
		$this->assertTrue( $this->NETWORK->getVersionTooOld( "1.0.0", "https://" . $this->bitBucketUrl ) );
	}

	/**
	 * @test
	 */
	function getGitCurrentOrNewer() {
		// curl module for netcurl will probably always be lower than the netcurl-version, so this is a good way of testing
		$tags           = $this->NETWORK->getGitTagsByUrl( "https://" . $this->bitBucketUrl );
		$lastTag        = array_pop( $tags );
		$lastBeforeLast = array_pop( $tags );
		// This should return false, since the current is not too old
		$isCurrent = $this->NETWORK->getVersionTooOld( $lastTag, "https://" . $this->bitBucketUrl );
		// This should return true, since the last version after the current is too old
		$isLastBeforeCurrent = $this->NETWORK->getVersionTooOld( $lastBeforeLast, "https://" . $this->bitBucketUrl );

		$this->assertTrue( $isCurrent === false && $isLastBeforeCurrent === true );
	}

	/**
	 * @test
	 */
	function timeoutChecking() {
		$def = $this->CURL->getTimeout();
		$this->CURL->setTimeout( 6 );
		$new = $this->CURL->getTimeout();
		$this->assertTrue( $def['connecttimeout'] == 300 && $def['requesttimeout'] == 0 && $new['connecttimeout'] == 3 && $new['requesttimeout'] == 6 );
	}

	/**
	 * @test
	 */
	function internalException() {
		$this->assertTrue( $this->NETWORK->getExceptionCode( 'NETCURL_EXCEPTION_IT_WORKS' ) == 1 );
	}

	/**
	 * @test
	 */
	function internalExceptionNoExists() {
		$this->assertTrue( $this->NETWORK->getExceptionCode( 'NETCURL_EXCEPTION_IT_DOESNT_WORK' ) == 500 );
	}

	/**
	 * @test
	 */
	function driverControlList() {
		$driverList = array();
		try {
			$driverList = $this->CURL->getDrivers();
		} catch ( \Exception $e ) {
			echo $e->getMessage();
		}
		$this->assertTrue( count( $driverList ) > 0 );
	}

	/**
	 * @test
	 */
	function driverControlNoList() {
		$driverList = false;
		try {
			$driverList = $this->CURL->getAvailableDrivers();
		} catch ( \Exception $e ) {
			echo $e->getMessage() . "\n";
		}
		$this->assertTrue( $driverList );
	}

	/**
	 * @test
	 */
	public function getCurrentProtocol() {
		$oneOfThenm = MODULE_NETWORK::getCurrentServerProtocol( true );
		$this->assertTrue( $oneOfThenm == "http" || $oneOfThenm == "https" );
	}

	/**
	 * @test
	 */
	function getSupportedDrivers() {
		$this->assertTrue( count( $this->CURL->getSupportedDrivers() ) > 0 );
	}

	/**
	 * @test
	 */
	function setAutoDriver() {
		$driverset = $this->CURL->setDriverAuto();
		$this->assertTrue( $driverset > 0 );
	}

	/**
	 * @test
	 */
	function getJsonByConstructor() {
		$identifierByJson = ( new MODULE_CURL( \TESTURLS::getUrlSimpleJson() ) )->getParsedResponse();
		$this->assertTrue( isset( $identifierByJson->ip ) );
	}

	/**
	 * @test
	 */
	function extractDomainIsGetUrlDomain() {
		$this->assertCount( 3, $this->NETWORK->getUrlDomain( "https://www.aftonbladet.se/uri/is/here" ) );
	}

	/**
	 * @test
	 * @testdox Safe mode and basepath cechking without paramters - in our environment, open_basedir is empty and safe_mode is off
	 */
	function getSafePermissionFull() {
		$this->assertFalse( $this->CURL->getIsSecure() );
	}

	/**
	 * @test
	 * @testdox Open_basedir is secured and (at least in our environment) safe_mode is disabled
	 */
	function getSafePermissionFullMocked() {
		ini_set( 'open_basedir', "/" );
		$this->assertTrue( $this->CURL->getIsSecure() );
		// Reset the setting as it is affecting other tests
		ini_set( 'open_basedir', "" );
	}

	/**
	 * @test
	 * @testdox open_basedir is safe and safe_mode-checking will be skipped
	 */
	function getSafePermissionFullMockedNoSafeMode() {
		ini_set( 'open_basedir', "/" );
		$this->assertTrue( $this->CURL->getIsSecure( false ) );
		// Reset the setting as it is affecting other tests
		ini_set( 'open_basedir', "" );
	}

	/**
	 * @test
	 * @testdox open_basedir is unsafe and safe_mode is mocked-active
	 */
	function getSafePermissionFullMockedSafeMode() {
		ini_set( 'open_basedir', "" );
		$this->assertTrue( $this->CURL->getIsSecure( true, true ) );
	}

	/**
	 * @test
	 * @testdox LIB-212
	 */
	function hasSsl() {
		$this->assertTrue($this->CURL->hasSsl());
	}

}