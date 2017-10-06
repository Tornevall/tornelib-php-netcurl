<?php

namespace TorneLIB;

require_once( '../vendor/autoload.php' );

use PHPUnit\Framework\TestCase;

ini_set( 'memory_limit', - 1 );    // Free memory limit, some tests requires more memory (like ip-range handling)

class Tornevall_cURLTest extends TestCase {
	private $StartErrorReporting;

	/** @var TorneLIB_Network */
	private $NET;
	/** @var Tornevall_cURL */
	private $CURL;
	/** @var TorneLIB_Crypto */
	private $Crypto;
	private $Urls;
	private $TorSetupAddress = "127.0.0.1:9050";
	private $TorSetupType = 4;      /* CURLPROXY_SOCKS4*/
	private $CurlVersion = null;

	// Compressed strings setup over base64
	private $testCompressString = "Testing my string";
	private $gz0Base = "H4sIAAAAAAAEAwERAO7_VGVzdGluZyBteSBzdHJpbmf030_XEQAAAA";
	private $gz9Base = "H4sIAAAAAAACAwtJLS7JzEtXyK1UKC4pArIA9N9P1xEAAAA";
	private $bzBase = "QlpoNDFBWSZTWajSZJAAAAETgEAABAACoxwgIAAhoaA0IBppoKc4F16DpQXi7kinChIVGkySAA";

	private $testLongCompressString = "The following string contains data: This is a longer string to test the best compression on something that is worth compression.";
	private $testLongCompressedString = "H4sIAAAAAAACA02MQQrDMAwEv6IX5N68Ix9QU9UyONpgLQTy-tqFQmFg9zBMuR_r5iZvtIarRpFkn7MjqDVSXkpdZfOaMlBpiGL9pxFCSwpH4znPjuPsllkRMkgcRv-arpyFC53-ry0flwqd0IQAAAA";

	public $specUrlUsername;
	public $specUrlPassword;

	//function tearDown() {}
	function setUp() {
		//$this->setDebug(true);
		$this->StartErrorReporting = error_reporting();
		$this->NET                 = new \TorneLIB\TorneLIB_Network();
		$this->CURL                = new \TorneLIB\Tornevall_cURL();
		$this->Crypto              = new \TorneLIB\TorneLIB_Crypto();

		if ( function_exists( 'curl_version' ) ) {
			$CurlVersionRequest = curl_version();
			$this->CurlVersion  = $CurlVersionRequest['version'];
		}

		/*
		 * Enable test mode
		 */
		$this->CURL->setTestEnabled();

		/*
		 * Set up testing URLS
		 */
		$this->Urls = array(
			'simple'      => 'http://identifier.tornevall.net/',
			'simplejson'  => 'http://identifier.tornevall.net/?json',
			'tests'       => 'developer.tornevall.net/tests/tornevall_network/',
			'soap'        => 'developer.tornevall.net/tests/tornevall_network/index.wsdl?wsdl',
			'httpcode'    => 'developer.tornevall.net/tests/tornevall_network/http.php',
			'selfsigned'  => 'https://dev-ssl-self.tornevall.nu',
			'mismatching' => 'https://dev-ssl-mismatch.tornevall.nu',
		);
	}

	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
	}

	private function pemDefault() {
		$this->CURL->_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION = false;
		$this->CURL->setSslUnverified( true );
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
		return $this->CURL->doGet( $this->Urls['simple'] );
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
		$theUrl = $this->getProtocol( $protocol ) . "://" . $this->Urls['tests'] . $indexFile . "?" . $parameters;

		return $this->CURL->doGet( $theUrl );
	}

	private function urlPost( $parameters = array(), $protocol = "http", $indexFile = 'index.php' ) {
		$theUrl = $this->getProtocol( $protocol ) . "://" . $this->Urls['tests'] . $indexFile;

		return $this->CURL->doPost( $theUrl, $parameters );
	}

	private function hasBody( $container ) {
		if ( is_array( $container ) && isset( $container['body'] ) ) {
			return true;
		}
	}

	private function getBody( $container ) {
		if ( $this->hasBody( $container ) ) {
			return $container['body'];
		}
	}

	private function getParsed( $container ) {
		if ( $this->hasBody( $container ) ) {
			return $container['parsed'];
		}

		return null;
	}

	/*
	function ignoreTestNoSsl()
	{
		if ($this->CURL->hasSsl()) {
			$this->markTestSkipped("This instance seems to have SSL available so we can't assume it doesn't");
		} else {
			$this->assertFalse($this->CURL->hasSsl());
		}
	}
	*/

	function testBase64GzEncodeLevel0() {
		$gzString = $this->Crypto->base64_gzencode( $this->testCompressString, 0 );
		$this->assertTrue( $gzString == $this->gz0Base );
	}

	function testBase64GzEncodeLevel9() {
		$myString = "Testing my string";
		$gzString = $this->Crypto->base64_gzencode( $myString, 9 );
		$this->assertTrue( $gzString == $this->gz9Base );
	}

	function testBase64GzDecodeLevel0() {
		$gzString = $this->Crypto->base64_gzdecode( $this->gz0Base );
		$this->assertTrue( $gzString == $this->testCompressString );
	}

	function testBase64GzDecodeLevel9() {
		$gzString = $this->Crypto->base64_gzdecode( $this->gz9Base );
		$this->assertTrue( $gzString == $this->testCompressString );
	}

	function testBase64BzEncode() {
		$bzString = $this->Crypto->base64_bzencode( $this->testCompressString );
		$this->assertTrue( $bzString == $this->bzBase );
	}

	function testBase64BzDecode() {
		$bzString = $this->Crypto->base64_bzdecode( $this->bzBase );
		$this->assertTrue( $bzString == $this->testCompressString );
	}

	function testBestCompression() {
		$compressedString                  = $this->Crypto->base64_compress( $this->testLongCompressString );
		$uncompressedString                = $this->Crypto->base64_decompress( $compressedString );
		$uncompressedStringCompressionType = $this->Crypto->base64_decompress( $compressedString, true );
		// In this case the compression type has really nothing to do with the test. We just know that gz9 is the best type for our chosen data string.
		$this->assertTrue( $uncompressedString == $this->testLongCompressString && $uncompressedStringCompressionType == "gz9" );
	}

	/**
	 * Runs a simple test to see if there is a container as it should
	 */
	function testSimpleGet() {
		$this->pemDefault();
		$container = $this->simpleGet();
		$this->assertTrue( $this->hasBody( $container ) );
	}

	function testSimpleGetProxy() {
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
			$ipType    = $this->NET->getArpaFromAddr( $container['body'], true );
			$this->assertTrue( $ipType > 0 );

			return;
		}
		$this->markTestSkipped( "I can't test this simpleGetProxy since there are no tor service installed" );
	}
	// Currently failing

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
	 * Fetch a response and immediately pick up the parsed response, from the internally stored last response
	 */
	function testGetParsedSelf() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$ParsedResponse = $this->CURL->getParsedResponse();
		$this->assertTrue( is_object( $ParsedResponse ) );
	}

	/**
	 * Make a direct call to the curl library
	 */
	function testQuickInitParsed() {
		$TempCurl = new Tornevall_cURL( "https://identifier.tornevall.net/?json" );
		$this->assertTrue( is_object( $TempCurl->getParsedResponse() ) );
	}

	/**
	 * Make a direct call to the curl library and get the response code
	 */
	function testQuickInitResponseCode() {
		$TempCurl = new Tornevall_cURL( "https://identifier.tornevall.net/?json" );
		$this->assertTrue( $TempCurl->getResponseCode() == 200 );
	}

	/**
	 * Make a direct call to the curl library and get the content of the body
	 */
	function testQuickInitResponseBody() {
		$TempCurl = new Tornevall_cURL( "https://identifier.tornevall.net/?json" );
		// Some content must exists in the body
		$this->assertTrue( strlen( $TempCurl->getResponseBody() ) >= 10 );
	}

	/**
	 * Fetch a response and immediately pick up the parsed response, from own content
	 */
	function testGetParsedFromResponse() {
		$this->pemDefault();
		$container      = $this->urlGet( "ssl&bool&o=json&method=get" );
		$ParsedResponse = $this->CURL->getParsedResponse( $container );
		$this->assertTrue( is_object( $ParsedResponse ) );
	}

	/**
	 * Request a specific value from a parsed response
	 */
	function testGetParsedValue() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$this->CURL->getParsedResponse();
		$ValueFrom = $this->CURL->getParsedValue( 'methods' );
		$this->assertTrue( is_object( $ValueFrom->_REQUEST ) );
	}

	/**
	 * Request a nested value from a parsed response
	 */
	function testGetParsedSubValue() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$ValueFrom = $this->CURL->getParsedValue( array( 'nesting', 'subarr4', 'child4' ) );
		$this->assertTrue( count( $ValueFrom ) === 3 );
	}

	/**
	 * Request a value by sending wrong value into the parser (crash test)
	 */
	function testGetParsedSubValueNoArray() {
		$this->pemDefault();
		$this->urlGet( "ssl&bool&o=json&method=get" );
		$ValueFrom = $this->CURL->getParsedValue( new \stdClass() );
		$this->assertTrue( empty( $ValueFrom ) );
	}

	/**
	 * Request a value that does not exist in a parsed response (Receive an exception)
	 */
	function testGetParsedSubValueFail() {
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
	 * Test if a web request has a valid body
	 */
	function testValidBody() {
		$this->pemDefault();
		$container = $this->simpleGet();
		$testBody  = $this->getBody( $container );
		$this->assertTrue( ! empty( $testBody ) );
	}

	/**
	 * Receive a standard 200 code
	 */
	function testSimple200() {
		$this->pemDefault();
		$simpleContent = $this->simpleGet();
		$this->assertTrue( is_array( $simpleContent ) && isset( $simpleContent['code'] ) && $simpleContent['code'] == 200 );
	}

	/**
	 * Test SSL based web request
	 */
	function testSslUrl() {
		$this->pemDefault();
		$container = $this->urlGet( "ssl&bool", "https" );
		$testBody  = $this->getBody( $container );
		$this->assertTrue( $this->getBody( $container ) && ! empty( $testBody ) );
	}

	function testSslSelfSignedException() {
		$this->pemDefault();
		try {
			$this->CURL->doGet( $this->Urls['selfsigned'] );
		} catch ( \Exception $e ) {
			$this->assertTrue( $e->getCode() == "60" );
		}
	}

	function testSslMismatching() {
		$this->pemDefault();
		try {
			$this->CURL->doGet( $this->Urls['selfsigned'] );
		} catch ( \Exception $e ) {
			$this->assertTrue( $e->getCode() == "60" );
		}
	}

	function testSslSelfSignedIgnore() {
		$this->pemDefault();
		try {
			$this->CURL->setSslVerify( false );
			$this->CURL->setSslUnverified( true );
			$container = $this->CURL->getParsedResponse( $this->CURL->doGet( $this->Urls['selfsigned'] . "/tests/tornevall_network/index.php?o=json&bool" ) );
			if ( is_object( $container ) ) {
				$this->assertTrue( isset( $container->methods ) );
			}
		} catch ( \Exception $e ) {
		}
	}

	/**
	 * Test that initially allows unverified ssl certificates should make netcurl to first call the url in a correct way and then,
	 * if this fails, make a quite risky failover into unverified mode - silently.
	 */
	function testSslSelfSignedUnverifyOnRun() {
		$this->pemDefault();
		try {
			$this->CURL->setSslUnverified( true );
			$container = $this->CURL->getParsedResponse( $this->CURL->doGet( $this->Urls['selfsigned'] . "/tests/tornevall_network/index.php?o=json&bool" ) );
			// The hasErrors function should return at least one error here
			if ( is_object( $container ) && count( $this->CURL->hasErrors() ) >= 1 ) {
				$this->assertTrue( isset( $container->methods ) );
			}
		} catch ( \Exception $e ) {
		}
	}

	/**
	 * Test parsed json response
	 */
	function testGetJson() {
		$this->pemDefault();
		$container = $this->urlGet( "ssl&bool&o=json&method=get" );
		$this->assertTrue( is_object( $container['parsed']->methods->_GET ) );
	}

	/**
	 * Check if we can parse a serialized response
	 */
	function testGetSerialize() {
		$this->pemDefault();
		$container = $this->urlGet( "ssl&bool&o=serialize&method=get" );
		$this->assertTrue( is_array( $container['parsed']['methods']['_GET'] ) );
	}

	/**
	 * Test if XML/Serializer are parsed correctly
	 */
	function testGetXmlSerializer() {
		$this->pemDefault();
		// XML_Serializer
		$container = $this->getParsed( $this->urlGet( "ssl&bool&o=xml&method=get" ) );
		$this->assertTrue( isset( $container->using ) && is_object( $container->using ) && $container->using['0'] == "XML/Serializer" );
	}

	/**
	 * Test if SimpleXml are parsed correctly
	 */
	function testGetSimpleXml() {
		$this->pemDefault();
		// SimpleXMLElement
		$container = $this->getParsed( $this->urlGet( "ssl&bool&o=xml&method=get&using=SimpleXMLElement" ) );
		$this->assertTrue( isset( $container->using ) && is_object( $container->using ) && $container->using == "SimpleXMLElement" );
	}

	/**
	 * Test if a html response are converted to a proper array
	 */
	function testGetSimpleDom() {
		$this->pemDefault();
		$this->CURL->setParseHtml( true );
		try {
			$container = $this->getParsed( $this->urlGet( "ssl&bool&o=xml&method=get&using=SimpleXMLElement", null, "simple.html" ) );
		} catch ( \Exception $e ) {

		}
		// ByNodes, ByClosestTag, ById
		$this->assertTrue( isset( $container['ById'] ) && count( $container['ById'] ) > 0 );
	}

	function testGetArpaLocalhost4() {
		$this->assertTrue( $this->NET->getArpaFromIpv4( "127.0.0.1" ) === "1.0.0.127" );
	}

	function testGetArpaLocalhost6() {
		$this->assertTrue( $this->NET->getArpaFromIpv6( "::1" ) === "1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0" );
	}

	function testGetArpaLocalhost4Second() {
		$this->assertTrue( $this->NET->getArpaFromIpv4( "192.168.12.36" ) === "36.12.168.192" );
	}

	function testGetArpaLocalhost6Second() {
		$this->assertTrue( $this->NET->getArpaFromIpv6( "2a01:299:a0:ff:10:128:255:2" ) === "2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2" );
	}

	function testGetArpaLocalhost4Nulled() {
		$this->assertEmpty( $this->NET->getArpaFromIpv4( null ) );
	}

	function testGetArpaLocalhost6Nulled() {
		$this->assertEmpty( $this->NET->getArpaFromIpv6( null ) );
	}

	function testGetArpaLocalhost4String() {
		$this->assertEmpty( $this->NET->getArpaFromIpv4( "fail here" ) );
	}

	function testGetArpaLocalhost6String() {
		$this->assertEmpty( $this->NET->getArpaFromIpv6( "fail here" ) );
	}

	function testGetArpaLocalhost6CorruptString1() {
		$this->assertEmpty( $this->NET->getArpaFromIpv6( "a : b \\" ) );
	}

	function testGetArpaLocalhost6CorruptString2() {
		$badString = "";
		for ( $i = 0; $i < 255; $i ++ ) {
			$badString .= chr( $i );
		}
		$this->assertEmpty( $this->NET->getArpaFromIpv6( $badString ) );
	}

	function testOctetV6() {
		$this->assertTrue( $this->NET->getIpv6FromOctets( "2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2" ) === "2a01:299:a0:ff:10:128:255:2" );
	}

	function testGetArpaAuto4() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "172.16.12.3" ) === "3.12.16.172" );
	}

	function testGetArpaAuto6() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "2a00:1450:400f:802::200e" ) === "e.0.0.2.0.0.0.0.0.0.0.0.0.0.0.0.2.0.8.0.f.0.0.4.0.5.4.1.0.0.a.2" );
	}

	function testGetIpType4() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "172.22.1.83", true ) === TorneLIB_Network_IP_Protocols::PROTOCOL_IPV4 );
	}

	function testGetIpType6() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "2a03:2880:f113:83:face:b00c:0:25de", true ) === TorneLIB_Network_IP_Protocols::PROTOCOL_IPV6 );
	}

	function testGetIpTypeFail() {
		$this->assertTrue( $this->NET->getArpaFromAddr( "This.Aint.An.Address", true ) === TorneLIB_Network_IP_Protocols::PROTOCOL_NONE );
	}

	function testMaskRangeArray24() {
		$this->assertCount( 255, $this->NET->getRangeFromMask( "192.168.1.0/24" ) );
	}

	function testMaskRangeArray16() {
		$this->assertCount( 65535, $this->NET->getRangeFromMask( "192.168.0.0/16" ) );
	}

	function testMaskRange24() {
		$this->assertTrue( $this->NET->isIpInRange( "192.168.1.55", "192.168.1.0/24" ) );
	}

	function testMaskRange24Fail() {
		$this->assertFalse( $this->NET->isIpInRange( "192.168.2.55", "192.168.1.0/24" ) );
	}

	function testMaskRange16() {
		$this->assertTrue( $this->NET->isIpInRange( "192.168.2.55", "192.168.0.0/16" ) );
	}

	function testMaskRange8() {
		$this->assertTrue( $this->NET->isIpInRange( "172.213.9.3", "172.0.0.0/8" ) );
	}
	/*
	function testMaskRangeArray8() {
		$this->assertCount(16777215, $this->NET->getRangeFromMask("192.0.0.0/8"));
	}
	*/

	/***************
	 *  SSL TESTS  *
	 **************/

	/**
	 * Test: SSL Certificates at custom location
	 * Expected Result: Successful lookup with verified peer
	 */
	function testSslCertLocation() {
		$this->CURL->_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION = true;
		$successfulVerification                            = false;
		try {
			$this->CURL->sslPemLocations = array( __DIR__ . "/ca-certificates.crt" );
			$container                   = $this->getParsed( $this->urlGet( "ssl&bool&o=json", "https" ) );
			$successfulVerification      = true;
		} catch ( \Exception $e ) {
		}
		$this->assertTrue( $successfulVerification );
	}

	/**
	 * Test: SSL Certificates at default location
	 * Expected Result: Successful lookup with verified peer
	 */
	function testSslDefaultCertLocation() {
		$this->pemDefault();

		$successfulVerification = false;
		try {
			$container              = $this->getParsed( $this->urlGet( "ssl&bool&o=json", "https" ) );
			$successfulVerification = true;
		} catch ( \Exception $e ) {
		}
		$this->assertTrue( $successfulVerification );
	}

	/**
	 * Test: SSL Certificates are missing and certificate location is mismatching
	 * Expected Result: Failing the url call
	 */
	function testFailingSsl() {
		$this->CURL->_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION = true;
		$successfulVerification                            = true;
		try {
			$this->CURL->setSslVerify( false );
			$this->CURL->setSslUnverified( true );
			$container = $this->getParsed( $this->urlGet( "ssl&bool&o=json", "https" ) );
		} catch ( \Exception $e ) {
			$successfulVerification = false;
		}
		$this->assertFalse( $successfulVerification );
	}

	/**
	 * Test: SSL Certificates are missing and peer verification is disabled
	 * Expected Result: Successful lookup with unverified peer
	 */
	function testUnverifiedSsl() {
		$this->CURL->_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION = true;
		$successfulVerification                            = false;
		$this->CURL->sslPemLocations                       = array( "non-existent-file" );
		try {
			$this->CURL->setSslUnverified( true );
			$container              = $this->getParsed( $this->urlGet( "ssl&bool&o=json", "https" ) );
			$successfulVerification = true;
		} catch ( \Exception $e ) {
		}
		$this->assertTrue( $successfulVerification );
	}

	private function getIpListByIpRoute() {
		// Don't fetch 127.0.0.1
		exec( "ip addr|grep \"inet \"|sed 's/\// /'|awk '{print $2}'|grep -v ^127", $returnedExecResponse );

		return $returnedExecResponse;
	}

	/**
	 * Test the customized ip address
	 */
	function testCustomIpAddrSimple() {
		$this->pemDefault();
		$returnedExecResponse = $this->getIpListByIpRoute();
		// Probably a bad shortcut for some systems, but it works for us in tests
		if ( ! empty( $returnedExecResponse ) && is_array( $returnedExecResponse ) ) {
			$NETWORK = new TorneLIB_Network();
			$ipArray = array();
			foreach ( $returnedExecResponse as $ip ) {
				// Making sure this test is running safely with non locals only
				if ( ! in_array( $ip, $ipArray ) && $NETWORK->getArpaFromAddr( $ip, true ) > 0 && ! preg_match( "/^10\./", $ip ) && ! preg_match( "/^172\./", $ip ) && ! preg_match( "/^192\./", $ip ) ) {
					$ipArray[] = $ip;
				}
			}
			$this->CURL->IpAddr = $ipArray;
			$CurlJson           = $this->CURL->doGet( $this->Urls['simplejson'] );
			$this->assertNotEmpty( $CurlJson['parsed']->ip );
		}
	}

	/**
	 * Test custom ip address setup (if more than one ip is set on the interface)
	 */
	function testCustomIpAddrAllString() {
		$this->pemDefault();
		$ipArray              = array();
		$responses            = array();
		$returnedExecResponse = $this->getIpListByIpRoute();
		if ( ! empty( $returnedExecResponse ) && is_array( $returnedExecResponse ) ) {
			$NETWORK = new TorneLIB_Network();
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
						$CurlJson = $this->CURL->doGet( $this->Urls['simplejson'] );
					} catch ( \Exception $e ) {

					}
					if ( isset( $CurlJson['parsed']->ip ) && $this->NET->getArpaFromAddr( $CurlJson['parsed']->ip, true ) > 0 ) {
						$responses[ $ip ] = $CurlJson['parsed']->ip;
					}
				}
			} else {
				$this->markTestSkipped( "ip address array is too short to be tested (" . print_R( $ipArray, true ) . ")" );
			}
		}
		$this->assertTrue( count( $responses ) === count( $ipArray ) );
	}

	/**
	 * Test proxy by using Tor Network (Requires Tor)
	 * @link https://www.torproject.org/ Required application
	 */
	function testTorNetwork() {
		$this->pemDefault();
		exec( "service tor status", $ubuntuService );
		$serviceFound = false;
		foreach ( $ubuntuService as $row ) {
			// Unsafe control
			if ( preg_match( "/loaded: loaded/i", $row ) ) {
				$serviceFound = true;
			}
		}
		if ( ! $serviceFound ) {
			$this->markTestSkipped( "Skip TOR Network tests: TOR Service not found in the current control" );
		} else {
			$this->CURL->setProxy( $this->TorSetupAddress, $this->TorSetupType );
			$CurlJson = $this->CURL->doGet( $this->Urls['simplejson'] );
			$parsedIp = $this->NET->getArpaFromAddr( $CurlJson['parsed']->ip, true );
			$this->assertTrue( $parsedIp > 0 );
		}
	}

	/**
	 * Run in default mode, when follows are enabled
	 */
	function testFollowRedirectEnabled() {
		$this->pemDefault();
		$redirectResponse = $this->CURL->doGet( "http://developer.tornevall.net/tests/tornevall_network/redirect.php?run" );
		$redirectedUrls   = $this->CURL->getRedirectedUrls();
		$this->assertTrue( $redirectResponse['code'] >= 300 && $redirectResponse['code'] <= 350 && preg_match( "/rerun/i", $redirectResponse['body'] ) && count( $redirectedUrls ) );
	}

	/**
	 * Run with redirect follows disabled
	 */
	function testFollowRedirectDisabled() {
		$this->pemDefault();
		$this->CURL->setEnforceFollowLocation( false );
		$redirectResponse = $this->CURL->doGet( "http://developer.tornevall.net/tests/tornevall_network/redirect.php?run" );
		$redirectedUrls   = $this->CURL->getRedirectedUrls();
		$this->assertTrue( $redirectResponse['code'] >= 300 && $redirectResponse['code'] <= 350 && ! preg_match( "/rerun/i", $redirectResponse['body'] ) && count( $redirectedUrls ) );
	}

	/**
	 * Run in a platform (deprecated) and make sure follows are disabled per default
	 */
	function testFollowRedirectSafeMode() {
		// http://php.net/manual/en/ini.sect.safe-mode.php
		if ( version_compare( PHP_VERSION, "5.4.0", ">=" ) ) {
			$this->markTestSkipped( "Safe mode has been removed from this platform, so tests can not be performed" );

			return;
		}
		if ( filter_var( ini_get( 'safe_mode' ), FILTER_VALIDATE_BOOLEAN ) === true ) {
			$this->pemDefault();
			$redirectResponse = $this->CURL->doGet( "http://developer.tornevall.net/tests/tornevall_network/redirect.php?run" );
			$redirectedUrls   = $this->CURL->getRedirectedUrls();
			$this->assertTrue( $redirectResponse['code'] >= 300 && $redirectResponse['code'] <= 350 && ! preg_match( "/rerun/i", $redirectResponse['body'] ) && count( $redirectedUrls ) );

			return;
		}
		$this->markTestSkipped( "Safe mode is available as an option. It is however not enabled on this platform and can not therefore be tested." );
	}

	/**
	 * This function should not end up in death
	 */
	function testMemberNull() {
		$localCurl = new Tornevall_cURL();
		$username  = $this->specUrlUsername;
		$password  = $this->specUrlPassword;
		if ( ! empty( $username ) ) {
			$this->markTestSkipped( "Can not run without credentials" );
		}
		$localCurl->setAuthentication( $username, $password, CURL_AUTH_TYPES::AUTHTYPE_BASIC );
		$specUrl = "https://omnitest.resurs.com/checkout/payments/null/updatePaymentReference";
		try {
			$null = $this->CURL->getParsedResponse( $localCurl->doPut( $specUrl, array( 'paymentReference' => null ), CURL_POST_AS::POST_AS_JSON ) );
		} catch ( \Exception $putUrlResponse ) {
		}
		$this->assertTrue( true );
	}

	function testSoapError() {
		$skipThis = true;
		if ( $skipThis ) {
			$this->markTestSkipped( "testSoapError is a special exceptions test. Normally we do not want to run this" );

			return;
		}
		$localCurl = new Tornevall_cURL();
		$wsdl      = $localCurl->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' );
		try {
			$wsdl->getPaymentMethods();
		} catch ( \Exception $e ) {
			$previousException = $e->getPrevious();
			$this->assertTrue( isset( $previousException->faultstring ) && ! empty( $previousException->faultstring ) && preg_match( "/unauthorized/i", $e->getMessage() ) );
		}
	}

	function testHostResolveValidationSuccess() {
		$localNetwork = new TorneLIB_Network();
		$localNetwork->setAlwaysResolveHostvalidation( true );
		$urlData = $localNetwork->getUrlDomain( "http://www.tornevall.net/" );
		$this->assertTrue( $urlData[0] == "www.tornevall.net" );
	}

	function testHostResolveValidationFail() {
		$localNetwork = new TorneLIB_Network();
		$localNetwork->setAlwaysResolveHostvalidation( true );
		$urlData = $localNetwork->getUrlDomain( "http://failing.domain/" );
		$this->assertTrue( $urlData[0] == "" );
	}

	function testHostValidationNoResolve() {
		$localNetwork = new TorneLIB_Network();
		$urlData      = $localNetwork->getUrlDomain( "http://failing.domain/" );
		$this->assertTrue( $urlData[0] == "failing.domain" );
	}

	/**
	 * Test SoapClient by making a standard doGet()
	 */
	function testSoapClient() {
		$assertThis = true;
		try {
			$this->CURL->doGet( "http://" . $this->Urls['soap'] );
		} catch ( \Exception $e ) {
			$assertThis = false;
		}
		$this->assertTrue( $assertThis );
	}

	/**
	 * Test Soap by internal controllers
	 */
	function testHasSoap() {
		$this->assertTrue( $this->CURL->hasSoap() );
	}

	function testBitStructure() {
		$myBits = array(
			'TEST1' => 1,
			'TEST2' => 2,
			'TEST4' => 4,
			'TEST8' => 8,
		);
		$myBit  = new TorneLIB_NetBits( $myBits );
		$this->assertCount( 9, $myBit->getBitStructure() );
	}

	/**
	 * Test if one bit is on (1)
	 */
	function testBitActive() {
		$myBits = array(
			'TEST1' => 1,
			'TEST2' => 2,
			'TEST4' => 4,
			'TEST8' => 8,
		);
		$myBit  = new TorneLIB_NetBits( $myBits );
		$this->assertTrue( $myBit->isBit( 8, 12 ) );
	}

	/**
	 * Test if one bit is off (0)
	 */
	function testBitNotActive() {
		$myBits = array(
			'TEST1' => 1,
			'TEST2' => 2,
			'TEST4' => 4,
			'TEST8' => 8,
		);
		$myBit  = new TorneLIB_NetBits( $myBits );
		$this->assertFalse( $myBit->isBit( 64, 12 ) );
	}

	/**
	 * Test if multiple bits are active (muliple settings by bit)
	 */
	function testMultiBitActive() {
		$myBits = array(
			'TEST1' => 1,
			'TEST2' => 2,
			'TEST4' => 4,
			'TEST8' => 8,
		);
		$myBit  = new TorneLIB_NetBits( $myBits );
		$this->assertTrue( $myBit->isBit( ( array( 8, 2 ) ), 14 ) );
	}

	/**
	 * Test correct returning bits
	 */
	function testBitArray() {
		$myBit    = new TorneLIB_NetBits();
		$bitArray = $myBit->getBitArray( "88" );      // 8 + 16 + 64
		$this->assertCount( 3, $bitArray );
	}

	/**
	 * Test large setup of bits
	 */
	function test16BitArray() {
		$myBit = new TorneLIB_NetBits();
		$myBit->setMaxBits( 16 );
		$bitArray = $myBit->getBitArray( ( 8 + 256 + 4096 + 8192 + 32768 ) );
		$this->assertCount( 5, $bitArray );
	}

	/**
	 * Test the same large setup of bits as above, but via the network library
	 */
	function testBitFromNet() {
		$this->NET = new TorneLIB_Network();
		$this->NET->BIT->setMaxBits( 16 );
		$bitArrList = $this->NET->BIT->getBitArray( 8 + 256 + 4096 + 8192 + 32768 );
		$this->assertCount( 5, $bitArrList );
	}

	function testBitModes() {
		$myBit    = array(
			'DEBIT'  => 1,
			'CREDIT' => 2,
			'ANNUL'  => 4
		);
		$bitClass = new TorneLIB_NetBits( $myBit );
		$bitArray = $bitClass->getBitArray( 255 );
		$this->assertTrue( in_array( 'DEBIT', $bitArray ) && in_array( 'CREDIT', $bitArray ) && in_array( 'ANNUL', $bitArray ) && in_array( 'BIT_128', $bitArray ) );
	}

	function testThrowable() {
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

	function testFailUrl() {
		try {
			$this->CURL->doGet( "http://abc" . sha1( microtime( true ) ) );
		} catch ( \Exception $e ) {
			$errorMessage = $e->getMessage();
			$this->assertTrue( ( preg_match( "/maximum tries/", $errorMessage ) ? true : false ) );
		}
	}
}