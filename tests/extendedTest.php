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
use \TorneLIB\MODULE_CURL;

ini_set( 'memory_limit', - 1 );    // Free memory limit, some tests requires more memory (like ip-range handling)

class extendedTest extends TestCase {

	/**
	 * @var MODULE_CURL $CURL
	 */
	private $CURL;
	private $username = "ecomphpPipelineTest";
	private $password = "4Em4r5ZQ98x3891D6C19L96TQ72HsisD";

	function setUp() {
		$this->CURL = new MODULE_CURL();
	}

	/**
	 * @test
	 * @testdox Testing an error type that comes from this specific service - testing if we can catch previous error instead of the current
	 * @throws \Exception
	 */
	function soapFaultstring() {
		$wsdl = $this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' );
		try {
			$wsdl->getPaymentMethods();
		} catch ( \Exception $e ) {
			$previousException = $e->getPrevious();
			$this->assertTrue( isset( $previousException->faultstring ) && ! empty( $previousException->faultstring ) && preg_match( "/unauthorized/i", $e->getMessage() ) );
		}
	}

	/**
	 * @test
	 * @testdox Testing unauthorized request by regular request (should give the same repsonse as soapFaultString)
	 * @throws \Exception
	 */
	function soapUnauthorized() {
		$wsdl = $this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' );
		try {
			$wsdl->getPaymentMethods();
		} catch ( \Exception $e ) {
			$this->assertTrue( preg_match( "/unauthorized/i", $e->getMessage() ) ? true : false );
		}
	}

	/**
	 * @test
	 * @testdox Test soapfaults when authentication are set up (as this generates other errors than without auth set)
	 * @throws \Exception
	 */
	function soapAuthErrorInitialSoapFaultsWsdl() {
		$this->CURL->setAuthentication( "fail", "fail" );
		// SOAPWARNINGS is set true by default on authentication activation
		try {
			$wsdl = $this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' );
			$wsdl->getPaymentMethods();
		} catch ( \Exception $e ) {
			$errorMessage = $e->getMessage();
			$errorCode    = $e->getCode();
			$this->assertTrue( $errorCode == 401 && preg_match( "/401 unauthorized/is", $errorMessage ) ? true : false );
		}
	}

	/**
	 * @test
	 * @testdox Post as SOAP, without the wsdl prefix
	 * @throws \Exception
	 */
	function soapAuthErrorInitialSoapFaultsNoWsdl() {
		$this->CURL->setSoapTryOnce( false );
		$this->CURL->setAuthentication( "fail", "fail" );
		try {
			$wsdl = $this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService', NETCURL_POST_DATATYPES::DATATYPE_SOAP );
			$wsdl->getPaymentMethods();
		} catch ( \Exception $e ) {
			$errorMessage = $e->getMessage();
			$errorCode    = $e->getCode();
			$this->assertTrue( $errorCode == 401 && preg_match( "/401 unauthorized/is", $errorMessage ) ? true : false );
		}
	}

	/**
	 * @test
	 * @testdox Running "old style failing authentication mode" should generate blind errors like here
	 * @throws \Exception
	 */
	function soapAuthErrorWithoutProtectiveFlag() {
		$this->CURL->setAuthentication( "fail", "fail" );
		$this->CURL->setFlag( "NOSOAPWARNINGS" );
		try {
			$this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' );
		} catch ( \Exception $e ) {
			// As of 6.0.16, SOAPWARNINGS are always enabled. Setting NOSOAPWARNINGS in flags, will render blind errors since the authentication errors are located in uncatchable warnings
			$errorCode = $e->getCode();
			$this->assertTrue( $errorCode == 500 ? true : false );
		}
	}

	/**
	 * @test
	 * @testdox
	 * @throws \Exception
	 */
	function soapAuthErrorNoInitialSoapFaultsNoWsdl() {
		$this->CURL->setAuthentication( "fail", "fail" );
		try {
			$this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService', NETCURL_POST_DATATYPES::DATATYPE_SOAP );
		} catch ( \Exception $e ) {
			// As of 6.0.16, this is the default behaviour even when SOAPWARNINGS are not active by setFlag
			$errorMessage = $e->getMessage();
			$errorCode    = $e->getCode();
			$this->assertTrue( $errorCode == 401 && preg_match( "/401 unauthorized/is", $errorMessage ) ? true : false );
		}
	}

	/**
	 * @test
	 * @throws \Exception
	 * @since 6.0.20
	 */
	function rbSoapChain() {
		$this->CURL->setFlag( "SOAPCHAIN" );
		$this->CURL->setAuthentication( $this->username, $this->password );
		try {
			$wsdlResponse = $this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' )->getPaymentMethods();
			$this->assertTrue( is_array( $wsdlResponse ) && count( $wsdlResponse ) > 1 );
		} catch ( \Exception $e ) {
			$this->markTestSkipped( __FUNCTION__ . ": " . $e->getMessage() );
		}
	}

	/**
	 * @test
	 * @testdox Test invalid function
	 * @throws \Exception
	 */
	function rbFailSoapChain() {
		$this->CURL->setFlag( "SOAPCHAIN" );
		$this->CURL->setAuthentication( $this->username, $this->password );
		try {
			$this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' )->getPaymentMethodz();
		} catch ( \Exception $e ) {
			$this->assertTrue( $e->getMessage() != "" );
		}
	}

	/**
	 * @test Go back to basics with NOSOAPCHAIN, since we as of 6.0.20 simplify get wsdl calls
	 * @throws \Exception
	 */
	function rbSoapBackToNoChain() {
		$this->CURL->setAuthentication( $this->username, $this->password );
		try {
			$wsdlResponse = $this->CURL->doGet( 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl' )->getPaymentMethods();
			$this->assertTrue( is_array( $this->CURL->getParsedResponse( $wsdlResponse ) ) && count( $this->CURL->getParsedResponse( $wsdlResponse ) ) > 1 );
		} catch ( \Exception $e ) {
			$this->markTestSkipped( __FUNCTION__ . ": " . $e->getMessage() );
		}
	}
}