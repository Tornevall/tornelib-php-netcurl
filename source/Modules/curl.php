<?php

/**
 * Copyright 2018 Tomas Tornevall & Tornevall Networks
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Tornevall Networks netCurl library - Yet another http- and network communicator library
 * Each class in this library has its own version numbering to keep track of where the changes are. However, there is a major version too.
 * @package TorneLIB
 * @version 6.0.20
 */

namespace TorneLIB;

if ( ! class_exists( 'MODULE_CURL' ) && ! class_exists( 'TorneLIB\MODULE_CURL' ) ) {

	if ( ! defined( 'NETCURL_CURL_RELEASE' ) ) {
		define( 'NETCURL_CURL_RELEASE', '6.0.18' );
	}
	if ( ! defined( 'NETCURL_CURL_MODIFIY' ) ) {
		define( 'NETCURL_CURL_MODIFIY', '20180320' );
	}
	if ( ! defined( 'NETCURL_CURL_CLIENTNAME' ) ) {
		define( 'NETCURL_CURL_CLIENTNAME', 'NetCurl' );
	}

	/**
	 * Class MOBILE_CURL
	 *
	 * @package TorneLIB
	 * @link https://docs.tornevall.net/x/KQCy TorneLIBv5
	 * @link https://bitbucket.tornevall.net/projects/LIB/repos/tornelib-php-netcurl/browse Sources of TorneLIB
	 * @link https://docs.tornevall.net/x/KwCy Network & Curl v5 and v6 Library usage
	 * @link https://docs.tornevall.net/x/FoBU TorneLIB Full documentation
	 */
	class MODULE_CURL {

		//// PUBLIC VARIABLES
		/**
		 * Default settings when initializing our curlsession.
		 *
		 * Since v6.0.2 no urls are followed by default, it is set internally by first checking PHP security before setting this up.
		 * The reason of the change is not only the security, it is also about inheritage of options to SOAPClient.
		 *
		 * @var array
		 */
		private $curlopt = array(
			CURLOPT_CONNECTTIMEOUT => 6,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_ENCODING       => 1,
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_USERAGENT      => 'TorneLIB-PHPcURL',
			CURLOPT_POST           => true,
			CURLOPT_SSLVERSION     => 4,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HTTPHEADER     => array( 'Accept-Language: en' ),
		);
		/** @var array User set SSL Options */
		private $sslopt = array();

		//// PUBLIC CONFIG THAT SHOULD GO PRIVATE
		/** @var array Default paths to the certificates we are looking for */
		private $sslPemLocations = array( '/etc/ssl/certs/cacert.pem', '/etc/ssl/certs/ca-certificates.crt' );
		/** @var array Interfaces to use */
		public $IpAddr = array();
		/** @var bool If more than one ip is set in the interfaces to use, this will make the interface go random */
		public $IpAddrRandom = true;
		/** @var null Sets a HTTP_REFERER to the http call */
		private $CurlReferer;

		/** @var $Drivers */
		private $Drivers = array();
		private $SupportedDrivers = array(
			'GuzzleHttp\Client' => NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP,
			'WP_Http'           => NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS
		);
		/** @var NETCURL_NETWORK_DRIVERS $currentDriver Current set driver */
		private $currentDriver;

		/** @var $PostData */
		private $PostData;
		/** @var $PostDataContainer */
		private $PostDataContainer;
		/** @var string $PostDataReal Post data as received from client */
		private $PostDataReal;

		private $userAgents = array(
			'Mozilla' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0;'
		);

		/**
		 * Die on use of proxy/tunnel on first try (Incomplete).
		 *
		 * This function is supposed to stop if the proxy fails on connection, so the library won't continue looking for a preferred exit point, since that will reveal the current unproxified address.
		 *
		 * @var bool
		 */
		private $DIE_ON_LOST_PROXY = true;

		//// PRIVATE AND PROTECTED VARIABLES VARIABLES
		/**
		 * Prepare MODULE_NETWORK class if it exists (as of the november 2016 it does).
		 *
		 * @var MODULE_NETWORK
		 */
		private $NETWORK;

		/**
		 * Target environment (if target is production some debugging values will be skipped)
		 *
		 * @since 5.0.0
		 * @var int
		 */
		private $TARGET_ENVIRONMENT = NETCURL_ENVIRONMENT::ENVIRONMENT_PRODUCTION;
		/** @var null Our communication channel */
		private $CurlSession = null;
		/** @var null URL that was set to communicate with */
		private $CurlURL = null;
		/** @var array Flags controller to change behaviour on internal function */
		//private $internalFlags = array();
		// Change to this flagSet when compatibility has been fixed
		private $internalFlags = array( 'CHAIN' => true );
		private $contentType;
		private $debugData = array(
			'data'     => array(
				'info' => array()
			),
			'soapData' => array(
				'info' => array()
			),
			'calls'    => 0
		);


		//// SSL AUTODETECTION CAPABILITIES
		/// DEFAULT: Most of the settings are set to be disabled, so that the system handles this automatically with defaults
		/// If there are problems reaching wsdl or connecting to https-based URLs, try set $testssl to true

		/**
		 * @var bool $testssl
		 *
		 * For PHP >= 5.6.0: If defined, try to guess if there is valid certificate bundles when using for example https links (used with openssl).
		 *
		 * This function activated by setting this value to true, tries to detect whether sslVerify should be used or not.
		 * The default value of this setting is normally false, since there should be no problems in a properly installed environment.
		 */
		private $testssl = true;
		/** @var bool Do not test certificates on older PHP-version (< 5.6.0) if this is false */
		private $testssldeprecated = false;
		/** @var bool If there are problems with certificates bound to a host/peer, set this to false if necessary. Default is to always try to verify them */
		private $sslVerify = true;
		/** @var array Error messages from SSL loading */
		private $sslDriverError = array();
		/** @var bool If SSL has been compiled in CURL, this will transform to true */
		private $sslCurlDriver = false;
		/** @var array Storage of invisible errors */
		private $hasErrorsStore = array();
		/**
		 * Allow https calls to unverified peers/hosts
		 *
		 * @since 5.0.0
		 * @var bool
		 */
		private $allowSslUnverified = false;
		/** @var bool During tests this will be set to true if certificate files is found */
		private $hasCertFile = false;
		/** @var string Defines what file to use as a certificate bundle */
		private $useCertFile = "";
		/** @var bool Shows if the certificate file found has been found internally or if it was set by user */
		private $hasDefaultCertFile = false;
		/** @var bool Shows if the certificate check has been runned */
		private $openSslGuessed = false;
		/** @var bool During tests this will be set to true if certificate directory is found */
		private $hasCertDir = false;

		//// IP AND PROXY CONFIG
		private $CurlIp = null;
		private $CurlIpType = null;
		/** @var null CurlProxy, if set, we will try to proxify the traffic */
		private $CurlProxy = null;
		/** @var null, if not set, but CurlProxy is, we will use HTTP as proxy (See CURLPROXY_* for more information) */
		private $CurlProxyType = null;
		/** @var bool Enable tunneling mode */
		private $CurlTunnel = false;

		//// URL REDIRECT
		/** @var bool Decide whether the curl library should follow an url redirect or not */
		private $followLocationSet = true;
		/** @var array List of redirections during curl calls */
		private $redirectedUrls = array();

		//// POST-GET-RESPONSE
		/** @var null A tempoary set of the response from the url called */
		private $TemporaryResponse = null;
		/** @var null Temporary response from external driver */
		private $TemporaryExternalResponse = null;
		/** @var NETCURL_POST_DATATYPES $forcePostType What post type to use when using POST (Enforced) */
		private $forcePostType = null;
		/** @var string Sets an encoding to the http call */
		public $CurlEncoding = null;
		/** @var array Run-twice-in-handler (replaces CurlResolveRetry, etc) */
		private $CurlRetryTypes = array( 'resolve' => 0, 'sslunverified' => 0 );
		/** @var string Custom User-Agent sent in the HTTP-HEADER */
		private $CurlUserAgent;
		/** @var string Custom User-Agent Memory */
		private $CustomUserAgent;
		/** @var bool Try to automatically parse the retrieved body content. Supports, amongst others json, serialization, etc */
		public $CurlAutoParse = true;
		/** @var bool Allow parsing of content bodies (tags) */
		private $allowParseHtml = false;
		private $ResponseType = NETCURL_RESPONSETYPE::RESPONSETYPE_ARRAY;
		/** @var array Authentication */
		private $AuthData = array(
			'Username' => null,
			'Password' => null,
			'Type'     => NETCURL_AUTH_TYPES::AUTHTYPE_NONE
		);
		/** @var array Adding own headers to the HTTP-request here */
		private $CurlHeaders = array();
		private $CurlHeadersSystem = array();
		private $CurlHeadersUserDefined = array();
		private $allowCdata = false;
		private $useXmlSerializer = false;
		/** @var bool Store information about the URL call and if the SSL was unsafe (disabled) */
		protected $unsafeSslCall = false;

		//// COOKIE CONFIGS
		private $useLocalCookies = false;
		private $CookiePath = null;
		private $SaveCookies = false;
		private $CookieFile = null;
		private $CookiePathCreated = false;
		private $UseCookieExceptions = false;
		public $AllowTempAsCookiePath = false;
		/** @var bool Use cookies and save them if needed (Normally not needed, but enabled by default) */
		public $CurlUseCookies = true;

		//// RESOLVING AND TIMEOUTS

		/**
		 * How to resolve hosts (Default = Not set)
		 *
		 * RESOLVER_IPV4
		 * RESOLVER_IPV6
		 *
		 * @var int
		 */
		public $CurlResolve;
		/** @var string Sets another timeout in seconds when curl_exec should finish the current operation. Sets both TIMEOUT and CONNECTTIMEOUT */
		private $CurlTimeout;
		private $CurlResolveForced = false;

		//// EXCEPTION HANDLING
		/** @var array Throwable http codes */
		private $throwableHttpCodes;
		/** @var bool By default, this library does not store any curl_getinfo during exceptions */
		private $canStoreSessionException = false;
		/** @var array An array that contains each curl_exec (curl_getinfo) when an exception are thrown */
		private $sessionsExceptions = array();
		/** @var bool The soapTryOnce variable */
		private $SoapTryOnce = true;
		private $curlConstantsOpt = array();
		private $curlConstantsErr = array();

		/**
		 * Set up if this library can throw exceptions, whenever it needs to do that.
		 *
		 * Note: This does not cover everything in the library. It was set up for handling SoapExceptions.
		 *
		 * @var bool
		 */
		public $canThrow = true;

		/**
		 * MODULE_CURL constructor.
		 *
		 * @param string $PreferredURL
		 * @param array $PreparedPostData
		 * @param int $PreferredMethod
		 * @param array $flags
		 *
		 * @throws \Exception
		 */
		public function __construct( $PreferredURL = '', $PreparedPostData = array(), $PreferredMethod = NETCURL_POST_METHODS::METHOD_POST, $flags = array() ) {
			register_shutdown_function( array( $this, 'tornecurl_terminate' ) );

			// PHP versions not supported to chaining gets the chaining parameter disabled by default.
			if ( version_compare( PHP_VERSION, "5.4.0", "<" ) ) {
				try {
					$this->setFlag( 'NOCHAIN', true );
				} catch ( \Exception $ignoreEmptyException ) {
					// This will never occur
				}
			}
			if ( is_array( $flags ) && count( $flags ) ) {
				$this->setFlags( $flags );
			}
			$this->NETWORK = new MODULE_NETWORK();
			$this->extractConstants();

			$authFlags = $this->getFlag( 'auth' );
			if ( isset( $authFlags['username'] ) && isset( $authFlags['password'] ) ) {
				$this->setAuthentication( $authFlags['username'], $authFlags['password'], isset( $authFlags['type'] ) ? $authFlags['type'] : NETCURL_AUTH_TYPES::AUTHTYPE_BASIC );
			}

			// Common ssl checkers (if they fail, there is a sslDriverError to recall
			if ( ! in_array( 'https', @stream_get_wrappers() ) ) {
				$this->sslDriverError[] = "SSL Failure: HTTPS wrapper can not be found";
			}
			if ( ! extension_loaded( 'openssl' ) ) {
				$this->sslDriverError[] = "SSL Failure: HTTPS extension can not be found";
			}
			// Initial setup
			$this->CurlUserAgent = $this->userAgents['Mozilla'] . ' +NetCurl-' . NETCURL_RELEASE . " +Curl-" . NETCURL_CURL_RELEASE . ')';
			if ( function_exists( 'curl_version' ) ) {
				$CurlVersionRequest = curl_version();
				$this->CurlVersion  = $CurlVersionRequest['version'];
				if ( defined( 'CURL_VERSION_SSL' ) ) {
					if ( isset( $CurlVersionRequest['features'] ) ) {
						$this->sslCurlDriver = ( $CurlVersionRequest['features'] & CURL_VERSION_SSL ? true : false );
						if ( ! $this->sslCurlDriver ) {
							$this->sslDriverError[] = 'SSL Failure: Protocol "https" not supported or disabled in libcurl';
						}
					} else {
						$this->sslDriverError[] = "SSL Failure: CurlVersionFeaturesList does not return any feature (this should not be happen)";
					}
				}
			}
			// If any of the above triggered an error, set curlDriver to false, as there may be problems during the
			// urlCall anyway. This library does not throw any error itself in those erros, since most of this kind of problems
			// are handled by curl itself. However, this opens for self checking in an early state through the hasSsl() function
			// and could be triggered long before the url calls are sent (and by means warn the developer that implements this solution
			// that there are an upcoming problem with the SSL support).
			if ( count( $this->sslDriverError ) ) {
				$this->sslCurlDriver = false;
			}
			$this->CurlResolve = NETCURL_RESOLVER::RESOLVER_DEFAULT;
			$this->openssl_guess();
			$this->throwableHttpCodes = array();

			if ( ! empty( $PreferredURL ) ) {
				$this->CurlURL   = $PreferredURL;
				$InstantResponse = null;
				if ( $PreferredMethod == NETCURL_POST_METHODS::METHOD_GET ) {
					$InstantResponse = $this->doGet( $PreferredURL );
				} else if ( $PreferredMethod == NETCURL_POST_METHODS::METHOD_POST ) {
					$InstantResponse = $this->doPost( $PreferredURL, $PreparedPostData );
				} else if ( $PreferredMethod == NETCURL_POST_METHODS::METHOD_PUT ) {
					$InstantResponse = $this->doPut( $PreferredURL, $PreparedPostData );
				} else if ( $PreferredMethod == NETCURL_POST_METHODS::METHOD_DELETE ) {
					$InstantResponse = $this->doDelete( $PreferredURL, $PreparedPostData );
				}

				return $InstantResponse;
			}

			return null;
		}

		/**
		 * Store constants of curl errors and curlOptions
		 */
		private function extractConstants() {
			try {
				$constants = @get_defined_constants();
				foreach ( $constants as $constKey => $constInt ) {
					if ( preg_match( "/^curlopt/i", $constKey ) ) {
						$this->curlConstantsOpt[ $constInt ] = $constKey;
					}
					if ( preg_match( "/^curle/i", $constKey ) ) {
						$this->curlConstantsErr[ $constInt ] = $constKey;
					}
				}
			} catch ( \Exception $constantException ) {
			}
			unset( $constants );
		}

		/**
		 * Ask this module whether there are available modules for use with http calls or not. Can also be set up to return a complete list of modules
		 *
		 * @param bool $getAsList
		 * @param bool $ignoreException Do not throw any exceptions on testing
		 *
		 * @return bool|array
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function getAvailableDrivers( $getAsList = false, $ignoreException = false ) {
			$hasExternalDrivers = false;
			// If this is not an array, we won't be able to count it.
			if ( ! is_array( $this->Drivers ) ) {
				$this->Drivers = array();
			}
			if ( count( $this->Drivers ) ) {
				$hasExternalDrivers = true;
			}
			$functionsDisabled = array_map( "trim", explode( ",", $this->getDisabledFunctions() ) );

			if ( function_exists( 'curl_init' ) && function_exists( 'curl_exec' ) ) {
				$this->Drivers[ NETCURL_NETWORK_DRIVERS::DRIVER_INTERNAL ] = true;
			}
			if ( ! $hasExternalDrivers && ! isset( $this->Drivers[ NETCURL_NETWORK_DRIVERS::DRIVER_INTERNAL ] ) ) {
				if ( $getAsList ) {
					return $this->Drivers;
				}
				if ( in_array( 'curl_init', $functionsDisabled ) || in_array( 'curl_exec', $functionsDisabled ) ) {
					if ( $ignoreException ) {
						return false;
					}
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " curl init exception: curl library has been disabled system wide", $this->NETWORK->getExceptionCode( 'NETCURL_CURL_DISABLED' ) );
				}
				if ( $ignoreException ) {
					return false;
				}
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " curl init exception: curl library not found", $this->NETWORK->getExceptionCode( 'NETCURL_CURL_MISSING' ) );
			}
			if ( $getAsList ) {
				return $this->Drivers;
			}

			return true;
		}

		/**
		 * Get a list of all available and supported Addons for the module
		 *
		 * @return array
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function getSupportedDrivers() {
			$supportedDrivers = $this->getAvailableDrivers( true );
			if ( ! is_array( $supportedDrivers ) ) {
				$supportedDrivers = array();
			}
			foreach ( $this->SupportedDrivers as $driverClass => $driverClassId ) {
				if ( class_exists( $driverClass ) ) {
					$supportedDrivers[ $driverClassId ] = true;
					// Guzzle supports both curl and stream so include it here
					if ( $driverClassId == NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ) {
						if ( ! $this->hasCurl() ) {
							unset( $supportedDrivers[ NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ] );
						}
						$supportedDrivers[ NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM ] = true;
					}
				}
			}

			return $supportedDrivers;
		}

		/**
		 * If the internal driver is available, we also consider curl available
		 *
		 * @return bool
		 * @throws \Exception
		 */
		private function hasCurl() {
			$driversList = $this->getAvailableDrivers( true );
			if ( isset( $driversList[ NETCURL_NETWORK_DRIVERS::DRIVER_INTERNAL ] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Automatically find the best suited driver for communication IF curl does not exist. If curl exists, internal driver will always be picked as first option
		 *
		 * @return int|null|string
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function setDriverAuto() {
			$firstAvailableDriver = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET;
			if ( ! $this->hasCurl() ) {
				$supportedDriverList  = $this->getSupportedDrivers();
				$supportedDriverCount = count( $supportedDriverList );
				if ( $supportedDriverCount ) {
					$firstAvailableDriver = key( $supportedDriverList );
					$this->setDriver( $firstAvailableDriver );
				}
			} else {
				return NETCURL_NETWORK_DRIVERS::DRIVER_INTERNAL;
			}
			if ( ! $supportedDriverCount ) {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: Could not set up a proper communications driver since none exist", $this->NETWORK->getExceptionCode( 'NETCURL_NOCOMM_DRIVER' ) );
			}

			return $firstAvailableDriver;
		}

		/**
		 * @return array
		 */
		public function getDebugData() {
			return $this->debugData;
		}

		/**
		 * Termination Controller - Used amongst others, to make sure that empty cookiepaths created by this library gets removed if they are being used.
		 */
		function tornecurl_terminate() {
			// If this indicates that we created the path, make sure it's removed if empty after session completion
			if ( ! count( glob( $this->CookiePath . "/*" ) ) && $this->CookiePathCreated ) {
				@rmdir( $this->CookiePath );
			}
		}

		/**
		 * @param array $arrayData
		 *
		 * @return bool
		 */
		function isAssoc( array $arrayData ) {
			if ( array() === $arrayData ) {
				return false;
			}

			return array_keys( $arrayData ) !== range( 0, count( $arrayData ) - 1 );
		}

		/**
		 * Set multiple flags
		 *
		 * @param array $flags
		 *
		 * @throws \Exception
		 * @since 6.0.10
		 */
		private function setFlags( $flags = array() ) {
			if ( $this->isAssoc( $flags ) ) {
				foreach ( $flags as $flagKey => $flagData ) {
					$this->setFlag( $flagKey, $flagData );
				}
			} else {
				foreach ( $flags as $flagKey ) {
					$this->setFlag( $flagKey, true );
				}
			}
			if ( $this->isFlag( "NOCHAIN" ) ) {
				$this->unsetFlag( "CHAIN" );
			}
		}

		/**
		 * Return all flags
		 *
		 * @return array
		 *
		 * @since 6.0.10
		 */
		public function getFlags() {
			return $this->internalFlags;
		}

		/**
		 * @param string $setContentTypeString
		 *
		 * @since 6.0.17
		 */
		public function setContentType( $setContentTypeString = 'application/json; charset=utf-8' ) {
			$this->contentType = $setContentTypeString;
		}

		/**
		 * @since 6.0.17
		 */
		public function getContentType() {
			return $this->contentType;
		}

		/**
		 * cUrl initializer, if needed faster
		 *
		 * @return resource
		 * @throws \Exception
		 * @since 5.0.0
		 */
		public function init() {
			$this->initCookiePath();
			if ( $this->hasCurl() ) {
				$this->CurlSession = curl_init( $this->CurlURL );
			}

			return $this->CurlSession;
		}

		/**
		 * Set up another driver for HTTP-requests
		 *
		 * Note: Soap calls are currently not supported through the WordPress driver, so using that one, will fall back to the SimpleSoap class.
		 *
		 * @param int $driverId
		 *
		 * @return bool
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function setDriver( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET ) {
			$isDriverSet = false;
			// Enforcing chaining will leave old clients incompatible
			//if ($driverId !== NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET) {
			//	$this->setFlag("CHAIN");
			//}

			$guzDrivers = array(
				NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP,
				NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM
			);
			if ( $driverId == NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS ) {
				if ( in_array( "WP_Http", get_declared_classes() ) ) {
					/** @noinspection PhpUndefinedClassInspection */
					$this->Drivers[ NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS ] = new \WP_Http();
					$isDriverSet                                                = true;
				}
			} else if ( in_array( $driverId, $guzDrivers ) ) {
				if ( $this->hasCurl() && $driverId === NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ) {
					// GuzzleHttp does not show up on get_declared_classes  in our tests, so we'll set the class in another way instead
					$isDriverSet = $this->setDriverByClass( $driverId, 'GuzzleHttp\Client' );
				} else {
					if ( class_exists( 'GuzzleHttp\Handler\StreamHandler' ) ) {
						/** @noinspection PhpUndefinedNamespaceInspection */
						/** @noinspection PhpUndefinedClassInspection */
						$streamHandler = new \GuzzleHttp\Handler\StreamHandler();
						$isDriverSet   = $this->setDriverByClass( $driverId, 'GuzzleHttp\Client', array( 'handler' => $streamHandler ) );
					} else {
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " setDriverException: GuzzleStream does not exists", $this->NETWORK->getExceptionCode( 'NETCURL_EXTERNAL_DRIVER_MISSING' ) );
					}
				}
			}

			$this->currentDriver = $driverId;

			return $isDriverSet;
		}

		/**
		 * Returns current chosen driver (if none is preset and curl exists, we're trying to use internals)
		 *
		 * @since 6.0.15
		 */
		public function getDriver() {
			if ( is_null( $this->currentDriver ) && $this->hasCurl() ) {
				$this->currentDriver = NETCURL_NETWORK_DRIVERS::DRIVER_INTERNAL;
			}

			return $this->currentDriver;
		}

		/**
		 * Get current configured http-driver
		 *
		 * @return mixed
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function getDrivers() {
			return $this->getAvailableDrivers( true );
		}

		/**
		 * @return string
		 * @since 6.0.14
		 */
		private function getDisabledFunctions() {
			return @ini_get( 'disable_functions' );
		}

		/**
		 * Set up driver by class name
		 *
		 * @param int $driverId
		 * @param string $className
		 * @param array $parameters
		 *
		 * @return bool
		 * @since 6.0.14
		 */
		private function setDriverByClass( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET, $className = '', $parameters = null ) {
			if ( class_exists( $className ) ) {
				if ( is_null( $parameters ) ) {
					$this->Drivers[ $driverId ] = new $className();
				} else {
					$this->Drivers[ $driverId ] = new $className( $parameters );
				}

				return true;
			}

			return false;
		}

		/**
		 * Check if driver with id is available and prepared
		 *
		 * @param int $driverId
		 *
		 * @return bool
		 * @since 6.0.14
		 */
		private function getIsDriver( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET ) {
			if ( isset( $this->Drivers[ $driverId ] ) && is_object( $this->Drivers[ $driverId ] ) ) {
				return true;
			}

			return false;
		}


		/**
		 * Set timeout for CURL, normally we'd like a quite short timeout here. Default: CURL default
		 *
		 * Affects connect and response timeout by below values:
		 *   CURLOPT_CONNECTTIMEOUT = ceil($timeout/2)    - How long a request is allowed to wait for conneciton, curl default = 300
		 *   CURLOPT_TIMEOUT = ceil($timeout)             - How long a request is allowed to take, curl default = never timeout (0)
		 *
		 * @param int $timeout
		 *
		 * @since 6.0.13
		 */
		public function setTimeout( $timeout = 6 ) {
			$this->CurlTimeout = $timeout;
		}

		/**
		 * Get current timeout setting
		 * @return array
		 * @since 6.0.13
		 */
		public function getTimeout() {
			$returnTimeouts = array(
				'connecttimeout' => ceil( $this->CurlTimeout / 2 ),
				'requesttimeout' => ceil( $this->CurlTimeout )
			);
			if ( empty( $this->CurlTimeout ) ) {
				$returnTimeouts = array(
					'connecttimeout' => 300,
					'requesttimeout' => 0
				);
			}

			return $returnTimeouts;
		}

		/**
		 * Initialize cookie storage
		 *
		 * @throws \Exception
		 */
		private function initCookiePath() {
			if ( defined( 'TORNELIB_DISABLE_CURL_COOKIES' ) || ! $this->useLocalCookies ) {
				return;
			}

			/**
			 * TORNEAPI_COOKIES has priority over TORNEAPI_PATH that is the default path
			 */
			if ( defined( 'TORNEAPI_COOKIES' ) ) {
				$this->CookiePath = TORNEAPI_COOKIES;
			} else {
				if ( defined( 'TORNEAPI_PATH' ) ) {
					$this->CookiePath = TORNEAPI_PATH . "/cookies";
				}
			}
			// If path is still empty after the above check, continue checking other paths
			if ( empty( $this->CookiePath ) || ( ! empty( $this->CookiePath ) && ! is_dir( $this->CookiePath ) ) ) {
				// We could use /tmp as cookie path but it is not recommended (which means this permission is by default disabled
				if ( $this->AllowTempAsCookiePath ) {
					if ( is_dir( "/tmp" ) ) {
						$this->CookiePath = "/tmp/";
					}
				} else {
					// However, if we still failed, we're trying to use a local directory
					$realCookiePath = realpath( __DIR__ . "/../cookies" );
					if ( empty( $realCookiePath ) ) {
						// Try to create a directory before bailing out
						$getCookiePath = realpath( __DIR__ . "/tornelib-php-netcurl/" );
						@mkdir( $getCookiePath . "/cookies/" );
						$this->CookiePathCreated = true;
						$this->CookiePath        = realpath( $getCookiePath . "/cookies/" );
					} else {
						$this->CookiePath = realpath( __DIR__ . "/../cookies" );
					}
					if ( $this->UseCookieExceptions && ( empty( $this->CookiePath ) || ! is_dir( $this->CookiePath ) ) ) {
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: Could not set up a proper cookiepath [To override this, use AllowTempAsCookiePath (not recommended)]", $this->NETWORK->getExceptionCode( 'NETCURL_COOKIEPATH_SETUP_FAIL' ) );
					}
				}
			}
		}

		/**
		 * Set internal flag parameter.
		 *
		 * @param string $flagKey
		 * @param string $flagValue Nullable since 6.0.10 = If null, then it is considered a true boolean, set setFlag("key") will always be true as an activation key
		 *
		 * @return bool If successful
		 * @throws \Exception
		 * @since 6.0.9
		 */
		public function setFlag( $flagKey = '', $flagValue = null ) {
			if ( ! empty( $flagKey ) ) {
				if ( is_null( $flagValue ) ) {
					$flagValue = true;
				}
				$this->internalFlags[ $flagKey ] = $flagValue;

				return true;
			}
			throw new \Exception( "Flags can not be empty", $this->NETWORK->getExceptionCode( 'NETCURL_SETFLAG_KEY_EMPTY' ) );
		}

		/**
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.10
		 */
		public function unsetFlag( $flagKey = '' ) {
			if ( $this->hasFlag( $flagKey ) ) {
				unset( $this->internalFlags[ $flagKey ] );

				return true;
			}

			return false;
		}

		/**
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.13 Consider using unsetFlag
		 */
		public function removeFlag( $flagKey = '' ) {
			return $this->unsetFlag( $flagKey );
		}

		/**
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.13 Consider using unsetFlag
		 */
		public function deleteFlag( $flagKey = '' ) {
			return $this->unsetFlag( $flagKey );
		}

		/**
		 * @since 6.0.13
		 */
		public function clearAllFlags() {
			$this->internalFlags = array();
		}

		/**
		 * Get internal flag
		 *
		 * @param string $flagKey
		 *
		 * @return mixed|null
		 * @since 6.0.9
		 */
		public function getFlag( $flagKey = '' ) {
			if ( isset( $this->internalFlags[ $flagKey ] ) ) {
				return $this->internalFlags[ $flagKey ];
			}

			return null;
		}

		/**
		 * Check if flag is set and true
		 *
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function isFlag( $flagKey = '' ) {
			if ( $this->hasFlag( $flagKey ) ) {
				return ( $this->getFlag( $flagKey ) === 1 || $this->getFlag( $flagKey ) === true ? true : false );
			}

			return false;
		}

		/**
		 * Check if there is an internal flag set with current key
		 *
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function hasFlag( $flagKey = '' ) {
			if ( ! is_null( $this->getFlag( $flagKey ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Enable chained mode ($Module->doGet(URL)->getParsedResponse()"
		 *
		 * @param bool $enable
		 *
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function setChain( $enable = true ) {
			if ( $enable ) {
				$this->setFlag( "CHAIN" );
			} else {
				$this->unsetFlag( "CHAIN" );
			}
		}

		public function getIsChained() {
			return $this->isFlag( "CHAIN" );
		}

		//// EXCEPTION HANDLING

		/**
		 * Throw on any code that matches the store throwableHttpCode (use with setThrowableHttpCodes())
		 *
		 * @param string $message
		 * @param string $code
		 *
		 * @throws \Exception
		 * @since 6.0.6
		 */
		private function throwCodeException( $message = '', $code = '' ) {
			if ( ! is_array( $this->throwableHttpCodes ) ) {
				$this->throwableHttpCodes = array();
			}
			foreach ( $this->throwableHttpCodes as $codeListArray => $codeArray ) {
				if ( isset( $codeArray[1] ) && $code >= intval( $codeArray[0] ) && $code <= intval( $codeArray[1] ) ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " HTTP Response Exception: " . $message, $code );
				}
			}
		}

		//// SESSION

		/**
		 * Returns an ongoing cUrl session - Normally you may get this from initSession (and normally you don't need this at all)
		 *
		 * @return null
		 */
		public function getCurlSession() {
			return $this->CurlSession;
		}


		//// PUBLIC SETTERS & GETTERS

		/**
		 * Allow fallback tests in SOAP mode
		 *
		 * Defines whether, when there is a SOAP-call, we should try to make the SOAP initialization twice.
		 * This is a kind of fallback when users forget to add ?wsdl or &wsdl in urls that requires this to call for SOAP.
		 * It may happen when setting NETCURL_POST_DATATYPES to a SOAP-call but, the URL is not defined as one.
		 * Setting this to false, may suppress important errors, since this will suppress fatal errors at first try.
		 *
		 * @param bool $enabledMode
		 *
		 * @since 6.0.9
		 */
		public function setSoapTryOnce( $enabledMode = true ) {
			$this->SoapTryOnce = $enabledMode;
		}

		/**
		 * Get the state of soapTryOnce
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function getSoapTryOnce() {
			return $this->SoapTryOnce;
		}


		/**
		 * Set the curl libraray to die, if no proxy has been successfully set up
		 *
		 * @param bool $dieEnabled
		 *
		 * @since 6.0.9
		 * @TODO Not implemented yet
		 */
		public function setDieOnNoProxy( $dieEnabled = true ) {
			$this->DIE_ON_LOST_PROXY = $dieEnabled;
		}

		/**
		 * Get the state of whether the library should bail out if no proxy has been successfully set
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function getDieOnNoProxy() {
			return $this->DIE_ON_LOST_PROXY;
		}

		/**
		 * Set up a list of which HTTP error codes that should be throwable (default: >= 400, <= 599)
		 *
		 * @param int $throwableMin Minimum value to throw on (Used with >=)
		 * @param int $throwableMax Maxmimum last value to throw on (Used with <)
		 *
		 * @since 6.0.6
		 */
		public function setThrowableHttpCodes( $throwableMin = 400, $throwableMax = 599 ) {
			$throwableMin               = intval( $throwableMin ) > 0 ? $throwableMin : 400;
			$throwableMax               = intval( $throwableMax ) > 0 ? $throwableMax : 599;
			$this->throwableHttpCodes[] = array( $throwableMin, $throwableMax );
		}

		/**
		 * Return the list of throwable http error codes (if set)
		 *
		 * @return array
		 * @since 6.0.6
		 */
		public function getThrowableHttpCodes() {
			return $this->throwableHttpCodes;
		}

		/**
		 * When using soap/xml fields returned as CDATA will be returned as text nodes if this is disabled (default: diabled)
		 *
		 * @param bool $enabled
		 *
		 * @since 5.0.0
		 */
		public function setCdata( $enabled = true ) {
			$this->allowCdata = $enabled;
		}

		/**
		 * Get current state of the setCdata
		 *
		 * @return bool
		 * @since 5.0.0
		 */
		public function getCdata() {
			return $this->allowCdata;
		}

		/**
		 * Enable the use of local cookie storage
		 *
		 * Use this only if necessary and if you are planning to cookies locally while, for example, needs to set a logged in state more permanent during get/post/etc
		 *
		 * @param bool $enabled
		 *
		 * @since 5.0.0
		 */
		public function setLocalCookies( $enabled = false ) {
			$this->useLocalCookies = $enabled;
		}

		/**
		 * Returns the current setting whether to use local cookies or not
		 * @return bool
		 * @since 6.0.6
		 */
		public function getLocalCookies() {
			return $this->useLocalCookies;
		}

		/**
		 * Enforce a response type if you're not happy with the default returned array.
		 *
		 * @param int $ResponseType
		 *
		 * @since 5.0.0
		 */
		public function setResponseType( $ResponseType = NETCURL_RESPONSETYPE::RESPONSETYPE_ARRAY ) {
			$this->ResponseType = $ResponseType;
		}

		/**
		 * Return the value of how the responses are returned
		 *
		 * @return int
		 * @since 6.0.6
		 */
		public function getResponseType() {
			return $this->ResponseType;
		}

		/**
		 * Enforce a specific type of post method
		 *
		 * To always send PostData, even if it is not set in the doXXX-method, you can use this setting to enforce - for example - JSON posts
		 * $myLib->setPostTypeDefault(NETCURL_POST_DATATYPES::DATATYPE_JSON)
		 *
		 * @param int $postType
		 *
		 * @since 6.0.6
		 */
		public function setPostTypeDefault( $postType = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$this->forcePostType = $postType;
		}

		/**
		 * Returns what to use as post method (NETCURL_POST_DATATYPES) on default. Returns null if none are set (= no overrides will be made)
		 * @return NETCURL_POST_DATATYPES
		 * @since 6.0.6
		 */
		public function getPostTypeDefault() {
			return $this->forcePostType;
		}

		/**
		 * Enforces CURLOPT_FOLLOWLOCATION to act different if not matching with the internal rules
		 *
		 * @param bool $setEnabledState
		 *
		 * @since 5.0.0/2017.4
		 */
		public function setEnforceFollowLocation( $setEnabledState = true ) {
			$this->followLocationSet = $setEnabledState;
		}

		/**
		 * Returns the boolean value of followLocationSet (see setEnforceFollowLocation)
		 * @return bool
		 * @since 6.0.6
		 */
		public function getEnforceFollowLocation() {
			return $this->followLocationSet;
		}

		/**
		 * Switch over to forced debugging
		 *
		 * To not break production environments by setting for example _DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION, switching over to test mode is required
		 * to use those variables.
		 *
		 * @since 5.0.0
		 */
		public function setTestEnabled() {
			$this->TARGET_ENVIRONMENT = NETCURL_ENVIRONMENT::ENVIRONMENT_TEST;
		}

		/**
		 * Returns current target environment
		 * @return int
		 * @since 6.0.6
		 */
		public function getTestEnabled() {
			return $this->TARGET_ENVIRONMENT;
		}

		/**
		 * Allow the initCookie-function to throw exceptions if the local cookie store can not be created properly
		 *
		 * Exceptions are invoked, normally when the function for initializing cookies can not create the storage directory. This is something you should consider disabled in a production environment.
		 *
		 * @param bool $enabled
		 */
		public function setCookieExceptions( $enabled = false ) {
			$this->UseCookieExceptions = $enabled;
		}

		/**
		 * Returns the boolean value set (eventually) from setCookieException
		 * @return bool
		 * @since 6.0.6
		 */
		public function getCookieExceptions() {
			return $this->UseCookieExceptions;
		}

		/**
		 * Set up whether we should allow html parsing or not
		 *
		 * @param bool $enabled
		 */
		public function setParseHtml( $enabled = false ) {
			$this->allowParseHtml = $enabled;
		}

		/**
		 * Return the boolean of the setParseHtml
		 * @return bool
		 */
		public function getParseHtml() {
			return $this->allowParseHtml;
		}

		/**
		 * Set up a different user agent for this library
		 *
		 * To make proper identification of the library we are always appending TorbeLIB+cUrl to the chosen user agent string.
		 *
		 * @param string $CustomUserAgent
		 */
		public function setUserAgent( $CustomUserAgent = "" ) {
			if ( ! empty( $CustomUserAgent ) ) {
				$this->CustomUserAgent .= preg_replace( "/\s+$/", '', $CustomUserAgent );
				$this->CurlUserAgent   = $this->CustomUserAgent . " +NetCurl-" . NETCURL_RELEASE . " +TorneLIB+cUrl-" . NETCURL_CURL_RELEASE;
			} else {
				$this->CurlUserAgent = $this->userAgents['Mozilla'] . ' +TorneLIB-NetCurl-' . NETCURL_RELEASE . " +TorneLIB+cUrl-" . NETCURL_CURL_RELEASE . ')';
			}
		}

		/**
		 * Returns the current set user agent
		 *
		 * @return string
		 */
		public function getUserAgent() {
			return $this->CurlUserAgent;
		}

		/**
		 * Get the value of customized user agent
		 *
		 * @return string
		 * @since 6.0.6
		 */
		public function getCustomUserAgent() {
			return $this->CustomUserAgent;
		}

		/**
		 * @param string $refererString
		 *
		 * @since 6.0.9
		 */
		public function setReferer( $refererString = "" ) {
			$this->CurlReferer = $refererString;
		}

		/**
		 * @return null
		 * @since 6.0.9
		 */
		public function getReferer() {
			return $this->CurlReferer;
		}

		/**
		 * If XML/Serializer exists in system, use that parser instead of SimpleXML
		 *
		 * @param bool $useIfExists
		 */
		public function setXmlSerializer( $useIfExists = true ) {
			$this->useXmlSerializer = $useIfExists;
		}

		/**
		 * Get the boolean value of whether to try to use XML/Serializer functions when fetching XML data
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getXmlSerializer() {
			return $this->useXmlSerializer;
		}

		/**
		 * Customize the curlopt configuration
		 *
		 * @param array|string $curlOptArrayOrKey If arrayed, there will be multiple options at once
		 * @param null $curlOptValue If not null, and the first parameter is not an array, this is taken as a single update value
		 *
		 * @throws \Exception
		 */
		public function setCurlOpt( $curlOptArrayOrKey = array(), $curlOptValue = null ) {
			if ( $this->hasCurl() ) {
				if ( is_null( $this->CurlSession ) ) {
					$this->init();
				}
				if ( is_array( $curlOptArrayOrKey ) ) {
					foreach ( $curlOptArrayOrKey as $key => $val ) {
						$this->curlopt[ $key ] = $val;
						curl_setopt( $this->CurlSession, $key, $val );
					}
				}
				if ( ! is_array( $curlOptArrayOrKey ) && ! empty( $curlOptArrayOrKey ) && ! is_null( $curlOptValue ) ) {
					$this->curlopt[ $curlOptArrayOrKey ] = $curlOptValue;
					curl_setopt( $this->CurlSession, $curlOptArrayOrKey, $curlOptValue );
				}
			}
		}

		/**
		 * curlops that can be overridden
		 *
		 * @param array|string $curlOptArrayOrKey
		 * @param null $curlOptValue
		 *
		 * @throws \Exception
		 */
		private function setCurlOptInternal( $curlOptArrayOrKey = array(), $curlOptValue = null ) {
			if ( $this->hasCurl() ) {
				if ( is_null( $this->CurlSession ) ) {
					$this->init();
				}
				if ( ! is_array( $curlOptArrayOrKey ) && ! empty( $curlOptArrayOrKey ) && ! is_null( $curlOptValue ) ) {
					if ( ! isset( $this->curlopt[ $curlOptArrayOrKey ] ) ) {
						$this->curlopt[ $curlOptArrayOrKey ] = $curlOptValue;
						curl_setopt( $this->CurlSession, $curlOptArrayOrKey, $curlOptValue );
					}
				}
			}
		}

		/**
		 * @return array
		 * @since 6.0.9
		 */
		public function getCurlOpt() {
			return $this->curlopt;
		}

		/**
		 * Easy readable curlopts
		 *
		 * @return array
		 * @since 6.0.10
		 */
		public function getCurlOptByKeys() {
			$return = array();
			if ( is_array( $this->curlConstantsOpt ) ) {
				$currentCurlOpt = $this->getCurlOpt();
				foreach ( $currentCurlOpt as $curlOptKey => $curlOptValue ) {
					if ( isset( $this->curlConstantsOpt[ $curlOptKey ] ) ) {
						$return[ $this->curlConstantsOpt[ $curlOptKey ] ] = $curlOptValue;
					} else {
						$return[ $curlOptKey ] = $curlOptValue;
					}
				}
			}

			return $return;
		}

		/**
		 * Set up special SSL option array for communicators
		 *
		 * @param array $sslOptArray
		 *
		 * @since 6.0.9
		 */
		public function setSslOpt( $sslOptArray = array() ) {
			foreach ( $sslOptArray as $key => $val ) {
				$this->sslopt[ $key ] = $val;
			}
		}

		/**
		 * Get current setup for SSL options
		 *
		 * @return array
		 * @since 6.0.9
		 */
		public function getSslOpt() {
			return $this->sslopt;
		}


		//// SINGLE PUBLIC GETTERS

		/**
		 * Get the current version of the module
		 *
		 * @param bool $fullRelease
		 *
		 * @return string
		 * @since 5.0.0
		 */
		public function getVersion( $fullRelease = false ) {
			if ( ! $fullRelease ) {
				return $this->TorneNetCurlVersion;
			} else {
				return $this->TorneNetCurlVersion . "-" . $this->TorneCurlReleaseDate;
			}
		}

		/**
		 * Get this internal release version
		 *
		 * Requires the constant TORNELIB_ALLOW_VERSION_REQUESTS to return any information.
		 *
		 * @return string
		 * @throws \Exception
		 * @deprecated Use tag control
		 */
		public function getInternalRelease() {
			if ( defined( 'TORNELIB_ALLOW_VERSION_REQUESTS' ) && TORNELIB_ALLOW_VERSION_REQUESTS === true ) {
				return $this->TorneNetCurlVersion . "," . $this->TorneCurlReleaseDate;
			}
			throw new \Exception( NETCURL_CURL_CLIENTNAME . " internalReleaseException [" . __CLASS__ . "]: Version requests are not allowed in current state (permissions required)", 403 );
		}

		/**
		 * Get store exceptions
		 * @return array
		 */
		public function getStoredExceptionInformation() {
			return $this->sessionsExceptions;
		}

		/// SPECIAL FEATURES

		/**
		 * @return bool
		 */
		public function hasErrors() {
			if ( ! count( $this->hasErrorsStore ) ) {
				return false;
			}

			return true;
		}

		/**
		 * @return array
		 */
		public function getErrors() {
			return $this->hasErrorsStore;
		}

		/**
		 * Check against Tornevall Networks API if there are updates for this module
		 *
		 * @param string $libName
		 *
		 * @return string
		 * @throws \Exception
		 */
		public function hasUpdate( $libName = 'tornelib_curl' ) {
			if ( ! defined( 'TORNELIB_ALLOW_VERSION_REQUESTS' ) ) {
				define( 'TORNELIB_ALLOW_VERSION_REQUESTS', true );
			}

			return $this->getHasUpdateState( $libName );
		}

		/**
		 * @param string $libName
		 *
		 * @return string
		 * @throws \Exception
		 */
		private function getHasUpdateState( $libName = 'tornelib_curl' ) {
			// Currently only supporting this internal module (through $myRelease).
			//$myRelease  = $this->getInternalRelease();
			$myRelease  = TORNELIB_NETCURL_RELEASE;
			$libRequest = ( ! empty( $libName ) ? "lib/" . $libName : "" );
			$getInfo    = $this->doGet( "https://api.tornevall.net/2.0/libs/getLibs/" . $libRequest . "/me/" . $myRelease );
			if ( isset( $getInfo['parsed']->response->getLibsResponse->you ) ) {
				$currentPublicVersion = $getInfo['parsed']->response->getLibsResponse->you;
				if ( $currentPublicVersion->hasUpdate ) {
					if ( isset( $getInfo['parsed']->response->getLibsResponse->libs->tornelib_curl ) ) {
						return $getInfo['parsed']->response->getLibsResponse->libs->tornelib_curl;
					}
				} else {
					return "";
				}
			}

			return "";
		}

		/**
		 * Returns true if SSL verification was unset during the URL call
		 *
		 * @return bool
		 * @since 6.0.10
		 */
		public function getSslIsUnsafe() {
			return $this->unsafeSslCall;
		}


		/// CONFIGURATORS

		/**
		 * Generate a correctified stream context depending on what happened in openssl_guess(), which also is running in this operation.
		 *
		 * Function created for moments when ini_set() fails in openssl_guess() and you don't want to "recalculate" the location of a valid certificates.
		 * This normally occurs in improper configured environments (where this bulk of functions actually also has been tested in).
		 * Recommendation of Usage: Do not copy only those functions, use the full version of tornevall_network.php since there may be dependencies in it.
		 *
		 * @return array
		 * @throws \Exception
		 * @link https://phpdoc.tornevall.net/TorneLIBv5/source-class-TorneLIB.Tornevall_cURL.html sslStreamContextCorrection() is a part of TorneLIB 5.0, described here
		 */
		public function sslStreamContextCorrection() {
			if ( ! $this->openSslGuessed ) {
				$this->openssl_guess( true );
			}
			$caCert    = $this->getCertFile();
			$sslVerify = true;
			$sslSetup  = array();
			if ( isset( $this->sslVerify ) ) {
				$sslVerify = $this->sslVerify;
			}
			if ( ! empty( $caCert ) ) {
				$sslSetup = array(
					'cafile'            => $caCert,
					'verify_peer'       => $sslVerify,
					'verify_peer_name'  => $sslVerify,
					'verify_host'       => $sslVerify,
					'allow_self_signed' => true
				);
			}

			return $sslSetup;
		}

		/**
		 * Automatically generates stream_context and appends it to whatever you need it for.
		 *
		 * Example:
		 *  $appendArray = array('http' => array("user_agent" => "MyUserAgent"));
		 *  $this->soapOptions = sslGetDefaultStreamContext($this->soapOptions, $appendArray);
		 *
		 * @param array $optionsArray
		 * @param array $selfContext
		 *
		 * @return array
		 * @throws \Exception
		 * @link http://developer.tornevall.net/apigen/TorneLIB-5.0/class-TorneLIB.Tornevall_cURL.html sslGetOptionsStream() is a part of TorneLIB 5.0, described here
		 */
		public function sslGetOptionsStream( $optionsArray = array(), $selfContext = array() ) {
			$streamContextOptions = array();
			if ( empty( $this->CurlUserAgent ) ) {
				$this->setUserAgent();
			}
			$streamContextOptions['http'] = array(
				"user_agent" => $this->CurlUserAgent
			);
			$sslCorrection                = $this->sslStreamContextCorrection();
			if ( count( $sslCorrection ) ) {
				$streamContextOptions['ssl'] = $this->sslStreamContextCorrection();
			}
			foreach ( $selfContext as $contextKey => $contextValue ) {
				$streamContextOptions[ $contextKey ] = $contextValue;
			}
			$optionsArray['stream_context'] = stream_context_create( $streamContextOptions );
			$this->sslopt                   = $optionsArray;

			return $optionsArray;
		}

		/**
		 * Set and/or append certificate bundle locations to current configuration
		 *
		 * @param array $locationArray
		 * @param bool $resetArray Make the location array go reset on customized list
		 *
		 */
		public function setSslPemLocations(
			$locationArray = array(
				'/etc/ssl/certs/cacert.pem',
				'/etc/ssl/certs/ca-certificates.crt'
			), $resetArray = false
		) {
			$newPem = array();
			if ( count( $this->sslPemLocations ) ) {
				foreach ( $this->sslPemLocations as $filePathAndName ) {
					if ( ! in_array( $filePathAndName, $newPem ) ) {
						$newPem[] = $filePathAndName;
					}
				}
			}
			if ( count( $locationArray ) ) {
				if ( $resetArray ) {
					$newPem = array();
				}
				foreach ( $locationArray as $filePathAndName ) {
					if ( ! in_array( $filePathAndName, $newPem ) ) {
						$newPem[] = $filePathAndName;
					}
				}
			}
			$this->sslPemLocations = $newPem;
		}

		/**
		 * Get current certificate bundle locations
		 *
		 * @return array
		 */
		public function getSslPemLocations() {
			return $this->sslPemLocations;
		}

		/**
		 * SSL Cerificate Handler
		 *
		 * This method tries to handle SSL Certification locations where PHP can't handle that part itself. In some environments (normally customized), PHP sometimes have
		 * problems with finding certificates, in case for example where they are not placed in standard locations. When running the testing, we will also try to set up
		 * a correct location for the certificates, if any are found somewhere else.
		 *
		 * The default configuration of this method is to run tests, but only for PHP 5.6.0 or higher.
		 * If you know that you're running something older you may want to consider enabling testssldeprecated.
		 *
		 * At first, the variable $testssl is used to automatically try to find out if there is valid certificate bundle installed on the running system. In PHP 5.6.0 and higher
		 * this procedure is simplified with the help of openssl_get_cert_locations(), which gives us a default path to installed certificates. In this case we will first look there
		 * for the certificate bundle. If we do fail there, or if your system is running something older, the testing are running in guessing mode.
		 *
		 * The method is untested in Windows server environments when using OpenSSL.
		 *
		 * @param bool $forceTesting Force testing even if $testssl is disabled
		 *
		 * @return bool
		 * @throws \Exception
		 * @link https://docs.tornevall.net/x/KwCy#TheNetworkandcURLclass(tornevall_network.php)-SSLCertificatesandverification
		 */
		private function openssl_guess( $forceTesting = false ) {
			// The certificate location here will be set up for the curl engine later on, during preparation of the connection.
			// NOTE: ini_set() does not work for setting up the cafile, this has to be done through php.ini, .htaccess, httpd.conf or .user.ini
			if ( ini_get( 'open_basedir' ) == '' ) {
				if ( $this->testssl || $forceTesting ) {
					$this->openSslGuessed = true;
					if ( version_compare( PHP_VERSION, "5.6.0", ">=" ) && function_exists( "openssl_get_cert_locations" ) ) {
						$locations = openssl_get_cert_locations();
						if ( is_array( $locations ) ) {
							if ( isset( $locations['default_cert_file'] ) ) {
								// If it exists, we don't have to bother anymore
								if ( file_exists( $locations['default_cert_file'] ) ) {
									$this->hasCertFile        = true;
									$this->useCertFile        = $locations['default_cert_file'];
									$this->hasDefaultCertFile = true;
								}
								if ( file_exists( $locations['default_cert_dir'] ) ) {
									$this->hasCertDir = true;
								}
								// For unit testing
								if ( $this->hasFlag( '_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION' ) && $this->isFlag( '_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION' ) ) {
									if ( $this->TARGET_ENVIRONMENT == NETCURL_ENVIRONMENT::ENVIRONMENT_TEST ) {
										// Enforce wrong certificate location
										$this->hasCertFile = false;
										$this->useCertFile = null;
									}
								}
							}
							// Check if the above control was successful - switch over to pemlocations if not.
							if ( ! $this->hasCertFile && is_array( $this->sslPemLocations ) && count( $this->sslPemLocations ) ) {
								// Loop through suggested locations and set the cafile in a variable if it's found.
								foreach ( $this->sslPemLocations as $pemLocation ) {
									if ( file_exists( $pemLocation ) ) {
										$this->useCertFile = $pemLocation;
										$this->hasCertFile = true;
									}
								}
							}
						}
						// On guess, disable verification if failed (if allowed)
						if ( ! $this->hasCertFile && $this->allowSslUnverified ) {
							$this->setSslVerify( false );
						}
					} else {
						// If we run on other PHP versions than 5.6.0 or higher, try to fall back into a known directory
						if ( $this->testssldeprecated ) {
							if ( ! $this->hasCertFile && is_array( $this->sslPemLocations ) && count( $this->sslPemLocations ) ) {
								// Loop through suggested locations and set the cafile in a variable if it's found.
								foreach ( $this->sslPemLocations as $pemLocation ) {
									if ( file_exists( $pemLocation ) ) {
										$this->useCertFile = $pemLocation;
										$this->hasCertFile = true;
									}
								}
								// For unit testing
								if ( $this->TARGET_ENVIRONMENT == NETCURL_ENVIRONMENT::ENVIRONMENT_TEST && $this->hasFlag( '_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION' ) && $this->isFlag( '_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION' ) ) {
									// Enforce wrong certificate location
									$this->hasCertFile = false;
									$this->useCertFile = null;
								}
							}
							// Check if the above control was successful - switch over to pemlocations if not.
							if ( ! $this->hasCertFile && $this->allowSslUnverified ) {
								$this->setSslVerify( false );
							}
						}
					}
				}
			} else {
				// Assume there is a valid certificate if jailed by open_basedir
				$this->hasCertFile = true;

				return true;
			}

			return $this->hasCertFile;
		}

		/**
		 * Enable/disable SSL Certificate autodetection (and/or host/peer ssl verications)
		 *
		 * The $hostVerification-flag can also be called manually with setSslVerify()
		 *
		 * @param bool $enabledFlag
		 * @param bool $hostVerification
		 */
		public function setCertAuto( $enabledFlag = true, $hostVerification = true ) {
			$this->testssl           = $enabledFlag;
			$this->testssldeprecated = $enabledFlag;
			$this->sslVerify         = $hostVerification;
		}

		/**
		 * Enable/disable SSL Peer/Host verification, if problems occur with certificates. If setCertAuto is enabled, this function will use best practice.
		 *
		 * @param bool $enabledFlag
		 *
		 * @return bool
		 * @throws \Exception
		 */
		public function setSslVerify( $enabledFlag = true ) {
			// allowSslUnverified is set to true, the enabledFlag is also allowed to be set to false
			if ( $this->allowSslUnverified ) {
				$this->sslVerify = $enabledFlag;
			} else {
				// If the enabledFlag is false and the allowance is not set, we will not be allowed to disabled SSL verification either
				if ( ! $enabledFlag ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " setSslVerify exception: setSslUnverified(true) has not been set", $this->NETWORK->getExceptionCode( 'NETCURL_SETSSLVERIFY_UNVERIFIED_NOT_SET' ) );
				} else {
					// However, if we force the verify flag to be on, we won't care about the allowance override, as the security
					// will be enhanced anyway.
					$this->sslVerify = $enabledFlag;
				}
			}

			return true;
		}

		/**
		 * Return the boolean value set in setSslVerify
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getSslVerify() {
			return $this->sslVerify;
		}

		/**
		 * While doing SSL calls, and SSL certificate verifications is failing, enable the ability to skip SSL verifications.
		 *
		 * Normally, we want a valid SSL certificate while doing https-requests, but sometimes the verifications must be disabled. One reason of this is
		 * in cases, when crt-files are missing and PHP can not under very specific circumstances verify the peer. To allow this behaviour, the client
		 * MUST use this function.
		 *
		 * @since 5.0.0
		 *
		 * @param bool $enabledFlag
		 */
		public function setSslUnverified( $enabledFlag = false ) {
			$this->allowSslUnverified = $enabledFlag;
		}

		/**
		 * Return the boolean value set from setSslUnverified
		 * @return bool
		 * @since 6.0.6
		 */
		public function getSslUnverified() {
			return $this->allowSslUnverified;
		}

		/**
		 * TestCerts - Test if your webclient has certificates available (make sure the $testssldeprecated are enabled if you want to test older PHP-versions - meaning older than 5.6.0)
		 *
		 * Note: This function also forces full ssl certificate checking.
		 *
		 * @return bool
		 * @throws \Exception
		 */
		public function TestCerts() {
			return $this->openssl_guess( true );
		}

		/**
		 * Return the current certificate bundle file, chosen by autodetection
		 * @return string
		 */
		public function getCertFile() {
			return $this->useCertFile;
		}

		/**
		 * Returns true if the autodetected certificate bundle was one of the defaults (normally fetched from openssl_get_cert_locations()). Used for testings.
		 *
		 * @return bool
		 */
		public function hasCertDefault() {
			return $this->hasDefaultCertFile;
		}

		/**
		 * @return bool
		 */
		public function hasSsl() {
			return $this->sslCurlDriver;
		}

		//// DEPRECATION (POSSIBLY EXTRACTABLE FROM NETWORK-LIBRARY)

		/**
		 * Extract domain name from URL
		 *
		 * @param string $url
		 *
		 * @return array
		 * @deprecated Use MODULE_NETWORK::getUrlDomain
		 */
		private function ExtractDomain( $url = '' ) {
			$urex   = explode( "/", preg_replace( "[^(.*?)//(.*?)/(.*)]", '$2', $url . "/" ) );
			$urtype = preg_replace( "[^(.*?)://(.*)]", '$1', $url . "/" );

			return array( $urex[0], $urtype );
		}


		//// IP SETUP

		/**
		 * Making sure the $IpAddr contains valid address list
		 *
		 * @throws \Exception
		 */
		private function handleIpList() {
			$this->CurlIp = null;
			$UseIp        = "";
			if ( is_array( $this->IpAddr ) ) {
				if ( count( $this->IpAddr ) == 1 ) {
					$UseIp = ( isset( $this->IpAddr[0] ) && ! empty( $this->IpAddr[0] ) ? $this->IpAddr[0] : null );
				} elseif ( count( $this->IpAddr ) > 1 ) {
					if ( ! $this->IpAddrRandom ) {
						// If we have multiple ip addresses in the list, but the randomizer is not active, always use the first address in the list.
						$UseIp = ( isset( $this->IpAddr[0] ) && ! empty( $this->IpAddr[0] ) ? $this->IpAddr[0] : null );
					} else {
						$IpAddrNum = rand( 0, count( $this->IpAddr ) - 1 );
						$UseIp     = $this->IpAddr[ $IpAddrNum ];
					}
				}
			} else if ( ! empty( $this->IpAddr ) ) {
				$UseIp = $this->IpAddr;
			}
			$ipType = $this->NETWORK->getArpaFromAddr( $UseIp, true );
			// Bind interface to specific ip only if any are found
			if ( $ipType == "0" ) {
				// If the ip type is 0 and it shows up there is something defined here, throw an exception.
				if ( ! empty( $UseIp ) ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: " . $UseIp . " is not a valid ip-address", $this->NETWORK->getExceptionCode( 'NETCURL_IPCONFIG_NOT_VALID' ) );
				}
			} else {
				$this->CurlIp = $UseIp;
				curl_setopt( $this->CurlSession, CURLOPT_INTERFACE, $UseIp );
				if ( $ipType == 6 ) {
					curl_setopt( $this->CurlSession, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6 );
					$this->CurlIpType = 6;
				} else {
					curl_setopt( $this->CurlSession, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
					$this->CurlIpType = 4;
				}
			}
		}

		/**
		 * Set up a proxy
		 *
		 * @param $ProxyAddr
		 * @param int $ProxyType
		 *
		 * @throws \Exception
		 */
		public function setProxy( $ProxyAddr, $ProxyType = CURLPROXY_HTTP ) {
			$this->CurlProxy     = $ProxyAddr;
			$this->CurlProxyType = $ProxyType;
			// Run from proxy on request
			$this->setCurlOptInternal( CURLOPT_PROXY, $this->CurlProxy );
			if ( isset( $this->CurlProxyType ) && ! empty( $this->CurlProxyType ) ) {
				$this->setCurlOptInternal( CURLOPT_PROXYTYPE, $this->CurlProxyType );
			}
		}

		/**
		 * Get proxy settings
		 *
		 * @return array
		 * @since 6.0.11
		 */
		public function getProxy() {
			return array(
				'curlProxy'     => $this->CurlProxy,
				'curlProxyType' => $this->CurlProxyType
			);
		}

		/**
		 * Enable curl tunneling
		 *
		 * @param bool $curlTunnelEnable
		 *
		 * @throws \Exception
		 * @since 6.0.11
		 */
		public function setTunnel( $curlTunnelEnable = true ) {
			// Run in tunneling mode
			$this->CurlTunnel = $curlTunnelEnable;
			$this->setCurlOptInternal( CURLOPT_HTTPPROXYTUNNEL, $curlTunnelEnable );
		}

		/**
		 * Return state of curltunneling
		 *
		 * @return bool
		 */
		public function getTunnel() {
			return $this->CurlTunnel;
		}


		//// PARSING

		/**
		 * Parse content and handle specially received content automatically
		 *
		 * If this functions receives a json string or any other special content (as PHP-serializations), it will try to convert that string automatically to a readable array.
		 *
		 * @param string $content
		 * @param bool $isFullRequest
		 * @param null $contentType
		 *
		 * @return array|mixed|null
		 * @throws \Exception
		 */
		public function ParseContent( $content = '', $isFullRequest = false, $contentType = null ) {
			if ( $isFullRequest ) {
				$newContent  = $this->ParseResponse( $content );
				$content     = $newContent['body'];
				$contentType = isset( $newContent['header']['info']['Content-Type'] ) ? $newContent['header']['info']['Content-Type'] : null;
			}
			$parsedContent     = null;
			$testSerialization = null;
			$testJson          = @json_decode( $content );
			if ( gettype( $testJson ) === "object" || ( ! empty( $testJson ) && is_array( $testJson ) ) ) {
				$parsedContent = $testJson;
			} else {
				if ( is_string( $content ) ) {
					$testSerialization = @unserialize( $content );
					if ( gettype( $testSerialization ) == "object" || gettype( $testSerialization ) === "array" ) {
						$parsedContent = $testSerialization;
					}
				}
			}
			if ( is_null( $parsedContent ) && ( preg_match( "/xml version/", $content ) || preg_match( "/rss version/", $content ) || preg_match( "/xml/i", $contentType ) ) ) {
				$trimmedContent        = trim( $content ); // PHP 5.3: Can't use function return value in write context
				$overrideXmlSerializer = false;
				if ( $this->useXmlSerializer ) {
					$serializerPath = stream_resolve_include_path( 'XML/Unserializer.php' );
					if ( ! empty( $serializerPath ) ) {
						$overrideXmlSerializer = true;
						/** @noinspection PhpIncludeInspection */
						require_once( 'XML/Unserializer.php' );
					}
				}

				if ( class_exists( 'SimpleXMLElement' ) && ! $overrideXmlSerializer ) {
					if ( ! empty( $trimmedContent ) ) {
						if ( ! $this->allowCdata ) {
							$simpleXML = new \SimpleXMLElement( $content, LIBXML_NOCDATA );
						} else {
							$simpleXML = new \SimpleXMLElement( $content );
						}
						if ( isset( $simpleXML ) && ( is_object( $simpleXML ) || is_array( $simpleXML ) ) ) {
							return $simpleXML;
						}
					} else {
						return null;
					}
				} else {
					// Returns empty class if the SimpleXMLElement is missing.
					if ( $overrideXmlSerializer ) {
						/** @noinspection PhpUndefinedClassInspection */
						$xmlSerializer = new \XML_Unserializer();
						/** @noinspection PhpUndefinedMethodInspection */
						$xmlSerializer->unserialize( $content );

						/** @noinspection PhpUndefinedMethodInspection */
						return $xmlSerializer->getUnserializedData();
					}

					return new \stdClass();
				}
			}
			if ( $this->allowParseHtml && empty( $parsedContent ) ) {
				if ( class_exists( 'DOMDocument' ) ) {
					$DOM = new \DOMDocument();
					libxml_use_internal_errors( true );
					$DOM->loadHTML( $content );
					if ( isset( $DOM->childNodes->length ) && $DOM->childNodes->length > 0 ) {
						$elementsByTagName = $DOM->getElementsByTagName( '*' );
						$childNodeArray    = $this->getChildNodes( $elementsByTagName );
						$childTagArray     = $this->getChildNodes( $elementsByTagName, 'tagnames' );
						$childIdArray      = $this->getChildNodes( $elementsByTagName, 'id' );
						$parsedContent     = array(
							'ByNodes'      => array(),
							'ByClosestTag' => array(),
							'ById'         => array()
						);
						if ( is_array( $childNodeArray ) && count( $childNodeArray ) ) {
							$parsedContent['ByNodes'] = $childNodeArray;
						}
						if ( is_array( $childTagArray ) && count( $childTagArray ) ) {
							$parsedContent['ByClosestTag'] = $childTagArray;
						}
						if ( is_array( $childIdArray ) && count( $childIdArray ) ) {
							$parsedContent['ById'] = $childIdArray;
						}
					}
				} else {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " HtmlParse exception: Can not parse DOMDocuments without the DOMDocuments class", $this->NETWORK->getExceptionCode( "NETCURL_DOMDOCUMENT_CLASS_MISSING" ) );
				}
			}

			return $parsedContent;
		}

		/**
		 * Parse response, in case of there is any followed traces from the curl engine, so we'll always land on the right ending stream
		 *
		 * @param string $content
		 *
		 * @return array|string|TORNELIB_CURLOBJECT
		 * @throws \Exception
		 */
		private function ParseResponse( $content = '' ) {
			// Kill the chaining (for future releases, when we eventually raise chaining mode as default)
			if ( $this->isFlag( "NOCHAIN" ) ) {
				$this->unsetFlag( "CHAIN" );
			}

			if ( ! is_string( $content ) ) {
				return $content;
			}
			list( $header, $body ) = explode( "\r\n\r\n", $content, 2 );
			$rows              = explode( "\n", $header );
			$response          = explode( " ", isset( $rows[0] ) ? $rows[0] : null );
			$shortCodeResponse = explode( " ", isset( $rows[0] ) ? $rows[0] : null, 3 );
			$httpMessage       = isset( $shortCodeResponse[2] ) ? $shortCodeResponse[2] : null;
			$code              = isset( $response[1] ) ? $response[1] : null;
			// If the first row of the body contains a HTTP/-string, we'll try to reparse it
			if ( preg_match( "/^HTTP\//", $body ) ) {
				$newBody = $this->ParseResponse( $body );
				if ( is_object( $newBody ) ) {
					$header = $newBody->TemporaryResponse['header'];
					$body   = $newBody->TemporaryResponse['body'];
				} else {
					$header = $newBody['header'];
					$body   = $newBody['body'];
				}
			}

			// If response code starts with 3xx, this is probably a redirect
			if ( preg_match( "/^3/", $code ) ) {
				$this->redirectedUrls[] = $this->CurlURL;
				$redirectArray[]        = array(
					'header' => $header,
					'body'   => $body,
					'code'   => $code
				);
				//if ( $this->isFlag( 'FOLLOWLOCATION_INTERNAL' ) ) {
				// For future coding only: Add internal follow function, eventually.
				//}
			}
			$headerInfo     = $this->GetHeaderKeyArray( $rows );
			$returnResponse = array(
				'header' => array( 'info' => $headerInfo, 'full' => $header ),
				'body'   => $body,
				'code'   => $code
			);

			$this->throwCodeException( $httpMessage, $code );
			if ( $this->CurlAutoParse ) {
				$contentType              = isset( $headerInfo['Content-Type'] ) ? $headerInfo['Content-Type'] : null;
				$parsedContent            = $this->ParseContent( $returnResponse['body'], false, $contentType );
				$returnResponse['parsed'] = ( ! empty( $parsedContent ) ? $parsedContent : null );
			}
			$returnResponse['URL'] = $this->CurlURL;
			$returnResponse['ip']  = isset( $this->CurlIp ) ? $this->CurlIp : null;  // Will only be filled if there is custom address set.

			if ( $this->ResponseType == NETCURL_RESPONSETYPE::RESPONSETYPE_OBJECT ) {
				// This is probably not necessary and will not be the default setup after all.
				$returnResponseObject         = new NETCURL_CURLOBJECT();
				$returnResponseObject->header = $returnResponse['header'];
				$returnResponseObject->body   = $returnResponse['body'];
				$returnResponseObject->code   = $returnResponse['code'];
				$returnResponseObject->parsed = $returnResponse['parsed'];
				$returnResponseObject->url    = $returnResponse['URL'];
				$returnResponseObject->ip     = $returnResponse['ip'];

				return $returnResponseObject;
			}
			$this->TemporaryResponse = $returnResponse;
			if ( $this->isFlag( "CHAIN" ) && ! $this->isFlag( 'IS_SOAP' ) ) {
				return $this;
			}

			return $returnResponse;
		}

		/**
		 * Experimental: Convert DOMDocument to an array
		 *
		 * @param array $childNode
		 * @param string $getAs
		 *
		 * @return array
		 */
		private function getChildNodes( $childNode = array(), $getAs = '' ) {
			$childNodeArray      = array();
			$childAttributeArray = array();
			$childIdArray        = array();
			$returnContext       = "";
			if ( is_object( $childNode ) ) {
				foreach ( $childNode as $nodeItem ) {
					if ( is_object( $nodeItem ) ) {
						if ( isset( $nodeItem->tagName ) ) {
							if ( strtolower( $nodeItem->tagName ) == "title" ) {
								$elementData['pageTitle'] = $nodeItem->nodeValue;
							}
							$elementData            = array( 'tagName' => $nodeItem->tagName );
							$elementData['id']      = $nodeItem->getAttribute( 'id' );
							$elementData['name']    = $nodeItem->getAttribute( 'name' );
							$elementData['context'] = $nodeItem->nodeValue;
							if ( $nodeItem->hasChildNodes() ) {
								$elementData['childElement'] = $this->getChildNodes( $nodeItem->childNodes, $getAs );
							}
							$identificationName = $nodeItem->tagName;
							if ( empty( $identificationName ) && ! empty( $elementData['name'] ) ) {
								$identificationName = $elementData['name'];
							}
							if ( empty( $identificationName ) && ! empty( $elementData['id'] ) ) {
								$identificationName = $elementData['id'];
							}
							$childNodeArray[] = $elementData;
							if ( ! isset( $childAttributeArray[ $identificationName ] ) ) {
								$childAttributeArray[ $identificationName ] = $elementData;
							} else {
								$childAttributeArray[ $identificationName ][] = $elementData;
							}
							if ( ! empty( $elementData['id'] ) ) {
								if ( ! isset( $childIdArray[ $elementData['id'] ] ) ) {
									$childIdArray[ $elementData['id'] ] = $elementData;
								} else {
									$childIdArray[ $elementData['id'] ][] = $elementData;
								}
							}
						}
					}
				}
			}
			if ( empty( $getAs ) || $getAs == "domnodes" ) {
				$returnContext = $childNodeArray;
			} else if ( $getAs == "tagnames" ) {
				$returnContext = $childAttributeArray;
			} else if ( $getAs == "id" ) {
				$returnContext = $childIdArray;
			}

			return $returnContext;
		}

		/**
		 * Get head and body from a request parsed
		 *
		 * @param string $content
		 *
		 * @return array
		 * @throws \Exception
		 */
		public function getHeader( $content = "" ) {
			return $this->ParseResponse( $content . "\r\n\r\n" );
		}

		/**
		 * Extract a parsed response from a webrequest
		 *
		 * @param null $ResponseContent
		 *
		 * @return null
		 * @throws \Exception
		 */
		public function getParsedResponse( $ResponseContent = null ) {
			if ( is_array( $ResponseContent ) ) {
				if ( isset( $ResponseContent['code'] ) && $ResponseContent['code'] >= 400 ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " parseResponse exception - Unexpected response code from server: " . $ResponseContent['code'], $ResponseContent['code'] );
				}
			}

			// When curl is disabled or missing, this might be returned chained
			if ( is_object( $ResponseContent ) ) {
				if ( method_exists( $ResponseContent, "getParsedResponse" ) && isset( $ResponseContent->TemporaryResponse ) && ! empty( $ResponseContent->TemporaryResponse ) ) {
					return $ResponseContent->getParsedResponse( $ResponseContent->TemporaryResponse );
				}

				return $ResponseContent;
			}
			if ( is_null( $ResponseContent ) && ! empty( $this->TemporaryResponse ) ) {
				return $this->TemporaryResponse['parsed'];
			} else if ( isset( $ResponseContent['parsed'] ) ) {
				return $ResponseContent['parsed'];
			}

			return null;
		}

		/**
		 * @return array
		 *
		 * @since 6.0.16
		 */
		public function getTemporaryResponse() {
			return $this->TemporaryResponse;
		}


		/**
		 * @param null $ResponseContent
		 *
		 * @return int
		 */
		public function getResponseCode( $ResponseContent = null ) {
			if ( method_exists( $ResponseContent, "getResponseCode" ) ) {
				return $ResponseContent->getResponseCode();
			}

			if ( is_null( $ResponseContent ) && ! empty( $this->TemporaryResponse ) && isset( $this->TemporaryResponse['code'] ) ) {
				return (int) $this->TemporaryResponse['code'];
			} else if ( isset( $ResponseContent['code'] ) ) {
				return (int) $ResponseContent['code'];
			}

			return 0;
		}

		/**
		 * @param null $ResponseContent
		 *
		 * @return null
		 */
		public function getResponseBody( $ResponseContent = null ) {
			if ( method_exists( $ResponseContent, "getResponseBody" ) ) {
				return $ResponseContent->getResponseBody();
			}

			if ( is_null( $ResponseContent ) && ! empty( $this->TemporaryResponse ) && isset( $this->TemporaryResponse['body'] ) ) {
				return $this->TemporaryResponse['body'];
			} else if ( isset( $ResponseContent['body'] ) ) {
				return $ResponseContent['body'];
			}

			return null;
		}

		/**
		 * @param null $ResponseContent
		 *
		 * @return string
		 * @since 6.0.16
		 */
		public function getResponseUrl( $ResponseContent = null ) {
			if ( method_exists( $ResponseContent, "getResponseUrl" ) ) {
				return $ResponseContent->getResponseUrl();
			}

			if ( is_null( $ResponseContent ) && ! empty( $this->TemporaryResponse ) && isset( $this->TemporaryResponse['body'] ) ) {
				return $this->TemporaryResponse['URL'];
			} else if ( isset( $ResponseContent['URL'] ) ) {
				return $ResponseContent['URL'];
			}

			return '';
		}

		/**
		 * Extract a specific key from a parsed webrequest
		 *
		 * @param $KeyName
		 * @param null $ResponseContent
		 *
		 * @return mixed|null
		 * @throws \Exception
		 */
		public function getParsedValue( $KeyName = null, $ResponseContent = null ) {
			if ( is_string( $KeyName ) ) {
				$ParsedValue = $this->getParsedResponse( $ResponseContent );
				if ( is_array( $ParsedValue ) && isset( $ParsedValue[ $KeyName ] ) ) {
					return $ParsedValue[ $KeyName ];
				}
				if ( is_object( $ParsedValue ) && isset( $ParsedValue->$KeyName ) ) {
					return $ParsedValue->$KeyName;
				}
			} else {
				if ( is_null( $ResponseContent ) && ! empty( $this->TemporaryResponse ) ) {
					$ResponseContent = $this->TemporaryResponse;
				}
				$Parsed       = $this->getParsedResponse( $ResponseContent );
				$hasRecursion = false;
				if ( is_array( $KeyName ) ) {
					$TheKeys  = array_reverse( $KeyName );
					$Eternity = 0;
					while ( count( $TheKeys ) || $Eternity ++ <= 20 ) {
						$hasRecursion = false;
						$CurrentKey   = array_pop( $TheKeys );
						if ( is_array( $Parsed ) ) {
							if ( isset( $Parsed[ $CurrentKey ] ) ) {
								$hasRecursion = true;
							}
						} else if ( is_object( $Parsed ) ) {
							if ( isset( $Parsed->$CurrentKey ) ) {
								$hasRecursion = true;
							}
						} else {
							// If there are still keys to scan, all tests above has failed
							if ( count( $TheKeys ) ) {
								$hasRecursion = false;
							}
							break;
						}
						if ( $hasRecursion ) {
							$Parsed = $this->getParsedValue( $CurrentKey, array( 'parsed' => $Parsed ) );
							// Break if this was the last one
							if ( ! count( $TheKeys ) ) {
								break;
							}
						}
					}
					if ( $hasRecursion ) {
						return $Parsed;
					} else {
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " getParsedValue exception: Requested key was not found in parsed response", $this->NETWORK->getExceptionCode( 'NETCURL_GETPARSEDVALUE_KEY_NOT_FOUND' ) );
					}
				}
			}

			return null;
		}

		public function getRedirectedUrls() {
			return $this->redirectedUrls;
		}

		/**
		 * Create an array of a header, with keys and values
		 *
		 * @param $HeaderRows
		 *
		 * @return array
		 */
		private function GetHeaderKeyArray( $HeaderRows ) {
			$headerInfo = array();
			foreach ( $HeaderRows as $headRow ) {
				$colon = array_map( "trim", explode( ":", $headRow, 2 ) );
				if ( isset( $colon[1] ) ) {
					$headerInfo[ $colon[0] ] = $colon[1];
				} else {
					$rowSpc = explode( " ", $headRow );
					if ( isset( $rowSpc[0] ) ) {
						$headerInfo[ $rowSpc[0] ] = $headRow;
					} else {
						$headerInfo[ $headRow ] = $headRow;
					}
				}
			}

			return $headerInfo;
		}

		/**
		 * Check if SOAP exists in system
		 *
		 * @param bool $extendedSearch Extend search for SOAP (unsafe method, looking for constants defined as SOAP_*)
		 *
		 * @return bool
		 */
		public function hasSoap( $extendedSearch = false ) {
			$soapClassBoolean = false;
			if ( ( class_exists( 'SoapClient' ) || class_exists( '\SoapClient' ) ) ) {
				$soapClassBoolean = true;
			}
			$sysConst = get_defined_constants();
			if ( in_array( 'SOAP_1_1', $sysConst ) || in_array( 'SOAP_1_2', $sysConst ) ) {
				$soapClassBoolean = true;
			} else {
				if ( $extendedSearch ) {
					foreach ( $sysConst as $constantKey => $constantValue ) {
						if ( preg_match( '/^SOAP_/', $constantKey ) ) {
							$soapClassBoolean = true;
						}
					}
				}
			}

			return $soapClassBoolean;
		}

		/**
		 * Return number of tries, arrayed, that different parts of netcurl has been trying to make a call
		 *
		 * @return array
		 * @since 6.0.8
		 */
		public function getRetries() {
			return $this->CurlRetryTypes;
		}

		/**
		 * Defines if this library should be able to store the curl_getinfo() for each curl_exec that generates an exception
		 *
		 * @param bool $Activate
		 */
		public function setStoreSessionExceptions( $Activate = false ) {
			$this->canStoreSessionException = $Activate;
		}

		/**
		 * Returns the boolean value of whether exceptions can be stored in memory during calls
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getStoreSessionExceptions() {
			return $this->canStoreSessionException;
		}

		/**
		 * Call cUrl with a POST
		 *
		 * @param string $url
		 * @param array $postData
		 * @param int $postAs
		 *
		 * @return array
		 * @throws \Exception
		 */
		public function doPost( $url = '', $postData = array(), $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeCurl( $url, $postData, NETCURL_POST_METHODS::METHOD_POST, $postAs );
				$response = $this->ParseResponse( $content );
			}

			return $response;
		}

		/**
		 * @param string $url
		 * @param array $postData
		 * @param int $postAs
		 *
		 * @return array
		 * @throws \Exception
		 */
		public function doPut( $url = '', $postData = array(), $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeCurl( $url, $postData, NETCURL_POST_METHODS::METHOD_PUT, $postAs );
				$response = $this->ParseResponse( $content );
			}

			return $response;
		}

		/**
		 * @param string $url
		 * @param array $postData
		 * @param int $postAs
		 *
		 * @return array
		 * @throws \Exception
		 */
		public function doDelete( $url = '', $postData = array(), $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeCurl( $url, $postData, NETCURL_POST_METHODS::METHOD_DELETE, $postAs );
				$response = $this->ParseResponse( $content );
			}

			return $response;
		}

		/**
		 * Call cUrl with a GET
		 *
		 * @param string $url
		 * @param int $postAs
		 *
		 * @return array|null|string|NETCURL_CURLOBJECT
		 * @throws \Exception
		 */
		public function doGet( $url = '', $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeCurl( $url, array(), NETCURL_POST_METHODS::METHOD_GET, $postAs );
				$response = $this->ParseResponse( $content );
			}

			return $response;
		}

		/**
		 * Enable authentication with cURL.
		 *
		 * @param null $Username
		 * @param null $Password
		 * @param int $AuthType Falls back on CURLAUTH_ANY if none are given. NETCURL_AUTH_TYPES are minimalistic since it follows the standards of CURLAUTH_
		 *
		 * @throws \Exception
		 */
		public function setAuthentication( $Username = null, $Password = null, $AuthType = NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
			$this->AuthData['Username'] = $Username;
			$this->AuthData['Password'] = $Password;
			$this->AuthData['Type']     = $AuthType;
			if ( $AuthType !== NETCURL_AUTH_TYPES::AUTHTYPE_NONE ) {
				// Default behaviour on authentications via SOAP should be to catch authfail warnings
				$this->setFlag( "SOAPWARNINGS", true );
			}
		}

		/**
		 * Fix problematic header data by converting them to proper outputs.
		 *
		 * @param array $headerList
		 */
		private function fixHttpHeaders( $headerList = array() ) {
			if ( is_array( $headerList ) && count( $headerList ) ) {
				foreach ( $headerList as $headerKey => $headerValue ) {
					$testHead = explode( ":", $headerValue, 2 );
					if ( isset( $testHead[1] ) ) {
						$this->CurlHeaders[] = $headerValue;
					} else {
						if ( ! is_numeric( $headerKey ) ) {
							$this->CurlHeaders[] = $headerKey . ": " . $headerValue;
						}
					}
				}
			}
		}

		/**
		 * Add extra curl headers
		 *
		 * @param string $key
		 * @param string $value
		 */
		public function setCurlHeader( $key = '', $value = '' ) {
			if ( ! empty( $key ) ) {
				$this->CurlHeadersUserDefined[ $key ] = $value;
			}
		}

		/**
		 * Return user defined headers
		 *
		 * @return array
		 * @since 6.0.6
		 */
		public function getCurlHeader() {
			return $this->CurlHeadersUserDefined;
		}

		/**
		 * Make sure that postdata is correctly rendered to interfaces before sending it
		 *
		 * @param array $postData
		 * @param int $postAs
		 *
		 * @return string
		 * @since 6.0.15
		 */
		private function executePostData( $postData = array(), $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$this->PostDataReal = $postData;
			$postDataContainer  = $postData;

			// Enforce postAs: If you'd like to force everything to use json you can for example use: $myLib->setPostTypeDefault(NETCURL_POST_DATATYPES::DATATYPE_JSON)
			if ( ! is_null( $this->forcePostType ) ) {
				$postAs = $this->forcePostType;
			}
			$parsedPostData = $postData;
			if ( is_array( $postData ) || is_object( $postData ) ) {
				$postDataContainer = http_build_query( $postData );
			}
			$this->PostDataContainer = $postDataContainer;

			if ( $postAs == NETCURL_POST_DATATYPES::DATATYPE_JSON ) {
				// Using $jsonRealData to validate the string
				$jsonRealData = null;
				if ( ! is_string( $postData ) ) {
					$jsonRealData = json_encode( $postData );
				} else {
					$testJsonData = json_decode( $postData );
					if ( is_object( $testJsonData ) || is_array( $testJsonData ) ) {
						$jsonRealData = $postData;
					}
				}
				$parsedPostData = $jsonRealData;
			}

			$this->PostData = $parsedPostData;

			return $parsedPostData;
		}

		/**
		 * cURL data handler, sets up cURL in what it believes is the correct set for you.
		 *
		 * @param string $url
		 * @param array $postData
		 * @param int $CurlMethod
		 * @param int $postAs
		 *
		 * @return mixed
		 * @throws \Exception
		 */
		private function executeCurl( $url = '', $postData = array(), $CurlMethod = NETCURL_POST_METHODS::METHOD_GET, $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			if ( ! empty( $url ) ) {
				$this->CurlURL = $url;
			}
			$this->getAvailableDrivers();

			$this->debugData['calls'] ++;

			if ( is_null( $this->CurlSession ) ) {
				$this->init();
			}
			$this->CurlHeaders = array();

			// Find out if CURLOPT_FOLLOWLOCATION can be set by user/developer or not.
			//
			// Make sure the safety control occurs even when the enforcing parameter is false.
			// This should prevent problems when $this->>followLocationSet is set to anything else than false
			// and security settings are higher for PHP. From v6.0.2, the in this logic has been simplified
			// to only set any flags if the security levels of PHP allows it, and only if the follow flag is enabled.
			//
			// Refers to http://php.net/manual/en/ini.sect.safe-mode.php
			if ( ini_get( 'open_basedir' ) == '' && ! filter_var( ini_get( 'safe_mode' ), FILTER_VALIDATE_BOOLEAN ) ) {
				// To disable the default behaviour of this function, use setEnforceFollowLocation([bool]).
				if ( $this->followLocationSet ) {
					// Since setCurlOptInternal is not an overrider, using the overrider here, will have no effect on the curlopt setting
					// as it has already been set from our top defaults. This has to be pushed in, by force.
					$this->setCurlOpt( CURLOPT_FOLLOWLOCATION, $this->followLocationSet );
				}
			}

			// If certificates missing (place above the wsdl, as it has to be inheritaged down to the soapclient
			if ( ! $this->TestCerts() ) {
				// And we're allowed to run without them
				if ( ! $this->sslVerify && $this->allowSslUnverified ) {
					// Then disable the checking here (overriders should always be enforced)
					$this->setCurlOpt( CURLOPT_SSL_VERIFYHOST, 0 );
					$this->setCurlOpt( CURLOPT_SSL_VERIFYPEER, 0 );
					$this->unsafeSslCall = true;
				} else {
					// From libcurl 7.28.1 CURLOPT_SSL_VERIFYHOST is deprecated. However, using the value 1 can be used
					// as of PHP 5.4.11, where the deprecation notices was added. The deprecation has started before libcurl
					// 7.28.1 (this was discovered on a server that was running PHP 5.5 and libcurl-7.22). In full debug
					// even libcurl-7.22 was generating this message, so from PHP 5.4.11 we are now enforcing the value 2
					// for CURLOPT_SSL_VERIFYHOST instead. The reason of why we are using the value 1 before this version
					// is actually a lazy thing, as we don't want to break anything that might be unsupported before this version.
					if ( version_compare( PHP_VERSION, '5.4.11', ">=" ) ) {
						$this->setCurlOptInternal( CURLOPT_SSL_VERIFYHOST, 2 );
					} else {
						$this->setCurlOptInternal( CURLOPT_SSL_VERIFYHOST, 1 );
					}
					$this->setCurlOptInternal( CURLOPT_SSL_VERIFYPEER, 1 );
				}
			} else {
				// Silently configure for https-connections, if exists
				if ( $this->useCertFile != "" && file_exists( $this->useCertFile ) ) {
					if ( ! $this->sslVerify && $this->allowSslUnverified ) {
						// Then disable the checking here
						$this->setCurlOpt( CURLOPT_SSL_VERIFYHOST, 0 );
						$this->setCurlOpt( CURLOPT_SSL_VERIFYPEER, 0 );
						$this->unsafeSslCall = true;
					} else {
						try {
							$this->setCurlOptInternal( CURLOPT_CAINFO, $this->useCertFile );
							$this->setCurlOptInternal( CURLOPT_CAPATH, dirname( $this->useCertFile ) );
						} catch ( \Exception $e ) {
						}
					}
				}
			}

			// Picking up externally select outgoing ip if any
			$this->handleIpList();

			// This curlopt makes it possible to make a call to a specific ip address and still use the HTTP_HOST (Must override)
			$this->setCurlOpt( CURLOPT_URL, $this->CurlURL );

			$this->executePostData( $postData, $postAs );
			$postDataContainer = $this->PostDataContainer;

			$domainArray = $this->ExtractDomain( $this->CurlURL );
			$domainName  = null;
			$domainHash  = null;
			if ( isset( $domainArray[0] ) ) {
				$domainName = $domainArray[0];
				$domainHash = md5( $domainName );
			}

			/**** CONDITIONAL SETUP ****/

			// Lazysession: Sets post data if any found and sends it even if the curl-method is GET or any other than POST
			// The postdata section must overwrite others, since the variables are set more than once depending on how the data
			// changes or gets converted. The internal curlOpt setter don't overwrite variables if they are alread set.
			if ( ! empty( $postDataContainer ) ) {
				$this->setCurlOpt( CURLOPT_POSTFIELDS, $postDataContainer );
			}
			if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_POST || $CurlMethod == NETCURL_POST_METHODS::METHOD_PUT || $CurlMethod == NETCURL_POST_METHODS::METHOD_DELETE ) {
				if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_PUT ) {
					$this->setCurlOpt( CURLOPT_CUSTOMREQUEST, "PUT" );
				} else if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_DELETE ) {
					$this->setCurlOpt( CURLOPT_CUSTOMREQUEST, "DELETE" );
				} else {
					$this->setCurlOpt( CURLOPT_POST, true );
				}

				if ( $postAs == NETCURL_POST_DATATYPES::DATATYPE_JSON ) {
					// Using $jsonRealData to validate the string
					//$jsonRealData = $this->executePostData($postData, $postAs);
					$this->CurlHeadersSystem['Content-Type']   = "application/json; charset=utf-8";
					$this->CurlHeadersSystem['Content-Length'] = strlen( $this->PostData );
					$this->setCurlOpt( CURLOPT_POSTFIELDS, $this->PostData );  // overwrite old
				}
			}

			// Self set timeouts, making sure the timeout set in the public is an integer over 0. Otherwise this falls back to the curldefauls.
			if ( isset( $this->CurlTimeout ) && $this->CurlTimeout > 0 ) {
				$this->setCurlOptInternal( CURLOPT_CONNECTTIMEOUT, ceil( $this->CurlTimeout / 2 ) );
				$this->setCurlOptInternal( CURLOPT_TIMEOUT, ceil( $this->CurlTimeout ) );
			}
			if ( isset( $this->CurlResolve ) && $this->CurlResolve !== NETCURL_RESOLVER::RESOLVER_DEFAULT ) {
				if ( $this->CurlResolve == NETCURL_RESOLVER::RESOLVER_IPV4 ) {
					$this->setCurlOptInternal( CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
				}
				if ( $this->CurlResolve == NETCURL_RESOLVER::RESOLVER_IPV6 ) {
					$this->setCurlOptInternal( CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6 );
				}
			}

			$this->setCurlOptInternal( CURLOPT_VERBOSE, false );
			// Tunnel and proxy setup. If this is set, make sure the default IP setup gets cleared out.
			if ( ! empty( $this->CurlProxy ) && ! empty( $this->CurlProxyType ) ) {
				unset( $this->CurlIp );
			}
			if ( $this->getTunnel() ) {
				unset( $this->CurlIp );
			}

			// Another HTTP_REFERER
			if ( isset( $this->CurlReferer ) && ! empty( $this->CurlReferer ) ) {
				$this->setCurlOptInternal( CURLOPT_REFERER, $this->CurlReferer );
			}

			$this->fixHttpHeaders( $this->CurlHeadersUserDefined );
			$this->fixHttpHeaders( $this->CurlHeadersSystem );

			if ( isset( $this->CurlHeaders ) && is_array( $this->CurlHeaders ) && count( $this->CurlHeaders ) ) {
				$this->setCurlOpt( CURLOPT_HTTPHEADER, $this->CurlHeaders ); // overwrite old
			}
			if ( isset( $this->CurlUserAgent ) && ! empty( $this->CurlUserAgent ) ) {
				$this->setCurlOpt( CURLOPT_USERAGENT, $this->CurlUserAgent ); // overwrite old
			}
			if ( isset( $this->CurlEncoding ) && ! empty( $this->CurlEncoding ) ) {
				$this->setCurlOpt( CURLOPT_ENCODING, $this->CurlEncoding ); // overwrite old
			}
			if ( file_exists( $this->CookiePath ) && $this->CurlUseCookies && ! empty( $this->CurlURL ) ) {
				@file_put_contents( $this->CookiePath . "/tmpcookie", "test" );
				if ( ! file_exists( $this->CookiePath . "/tmpcookie" ) ) {
					$this->SaveCookies = true;
					$this->CookieFile  = $domainHash;
					$this->setCurlOptInternal( CURLOPT_COOKIEFILE, $this->CookiePath . "/" . $this->CookieFile );
					$this->setCurlOptInternal( CURLOPT_COOKIEJAR, $this->CookiePath . "/" . $this->CookieFile );
					$this->setCurlOptInternal( CURLOPT_COOKIE, 1 );
				} else {
					if ( file_exists( $this->CookiePath . "/tmpcookie" ) ) {
						unlink( $this->CookiePath . "/tmpcookie" );
					}
					$this->SaveCookies = false;
				}
			} else {
				$this->SaveCookies = false;
			}

			if ( ! empty( $this->AuthData['Username'] ) ) {
				$useAuth = $this->AuthData['Type'];
				if ( $this->AuthData['Type'] != NETCURL_AUTH_TYPES::AUTHTYPE_NONE ) {
					$useAuth = CURLAUTH_ANY;
					if ( $this->AuthData['Type'] == NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
						$useAuth = CURLAUTH_BASIC;
					}
				}
				$this->setCurlOptInternal( CURLOPT_HTTPAUTH, $useAuth );
				$this->setCurlOptInternal( CURLOPT_USERPWD, $this->AuthData['Username'] . ':' . $this->AuthData['Password'] );
			}

			// UNCONDITIONAL SETUP
			// Things that should be overwritten if set by someone else
			$this->setCurlOpt( CURLOPT_HEADER, true );
			$this->setCurlOpt( CURLOPT_RETURNTRANSFER, true );
			$this->setCurlOpt( CURLOPT_AUTOREFERER, true );
			$this->setCurlOpt( CURLINFO_HEADER_OUT, true );

			// Override with SoapClient just before the real curl_exec is the most proper way to handle inheritages
			if ( preg_match( "/\?wsdl$|\&wsdl$/i", $this->CurlURL ) || $postAs == NETCURL_POST_DATATYPES::DATATYPE_SOAP ) {
				if ( ! $this->hasSoap() ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: SoapClient is not available in this system", $this->NETWORK->getExceptionCode( 'NETCURL_SOAPCLIENT_CLASS_MISSING' ) );
				}

				return $this->executeHttpSoap( $url, $postData, $CurlMethod );
			}

			$externalExecute = $this->executeHttpExternal( $url, $postData, $CurlMethod, $postAs );

			$isExternalDriver = $this->getDriver();
			if ( ( $externalExecute !== true && $this->hasCurl() ) && $isExternalDriver === NETCURL_NETWORK_DRIVERS::DRIVER_INTERNAL ) {
				$returnContent = curl_exec( $this->CurlSession );
				if ( curl_errno( $this->CurlSession ) ) {

					$this->debugData['data']['url'][] = array(
						'url'       => $this->CurlURL,
						'opt'       => $this->getCurlOptByKeys(),
						'success'   => false,
						'exception' => curl_error( $this->CurlSession )
					);

					if ( $this->canStoreSessionException ) {
						$this->sessionsExceptions[] = array(
							'Content'     => $returnContent,
							'SessionInfo' => curl_getinfo( $this->CurlSession )
						);
					}
					$errorCode    = curl_errno( $this->CurlSession );
					$errorMessage = curl_error( $this->CurlSession );
					if ( $this->CurlResolveForced && $this->CurlRetryTypes['resolve'] >= 2 ) {
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception in " . __FUNCTION__ . ": The maximum tries of curl_exec() for " . $this->CurlURL . " has been reached without any successful response. Normally, this happens after " . $this->CurlRetryTypes['resolve'] . " CurlResolveRetries and might be connected with a bad URL or similar that can not resolve properly.\nCurl error message follows: " . $errorMessage, $errorCode );
					}
					if ( $errorCode == CURLE_SSL_CACERT || $errorCode === 60 && $this->allowSslUnverified ) {
						if ( $this->CurlRetryTypes['sslunverified'] >= 2 ) {
							throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception in " . __FUNCTION__ . ": The maximum tries of curl_exec() for " . $this->CurlURL . ", during a try to make a SSL connection to work, has been reached without any successful response. This normally happens when allowSslUnverified is activated in the library and " . $this->CurlRetryTypes['resolve'] . " tries to fix the problem has been made, but failed.\nCurl error message follows: " . $errorMessage, $errorCode );
						} else {
							$this->hasErrorsStore[] = array( 'code' => $errorCode, 'message' => $errorMessage );
							$this->setSslVerify( false );
							$this->setSslUnverified( true );
							$this->unsafeSslCall = true;
							$this->CurlRetryTypes['sslunverified'] ++;

							return $this->executeCurl( $this->CurlURL, $postData, $CurlMethod );
						}
					}
					if ( $errorCode == CURLE_COULDNT_RESOLVE_HOST || $errorCode === 45 ) {
						$this->hasErrorsStore[] = array( 'code' => $errorCode, 'message' => $errorMessage );
						$this->CurlRetryTypes['resolve'] ++;
						unset( $this->CurlIp );
						$this->CurlResolveForced = true;
						if ( $this->CurlIpType == 6 ) {
							$this->CurlResolve = NETCURL_RESOLVER::RESOLVER_IPV4;
						}
						if ( $this->CurlIpType == 4 ) {
							$this->CurlResolve = NETCURL_RESOLVER::RESOLVER_IPV6;
						}

						return $this->executeCurl( $this->CurlURL, $postData, $CurlMethod );
					}
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception from PHP/CURL at " . __FUNCTION__ . ": " . curl_error( $this->CurlSession ), curl_errno( $this->CurlSession ) );
				} else {
					$this->debugData['data']['url'][] = array(
						'url'       => $this->CurlURL,
						'opt'       => $this->getCurlOptByKeys(),
						'success'   => true,
						'exception' => null
					);
				}
			}
			if ( ! isset( $returnContent ) && ! empty( $externalExecute ) ) {
				$returnContent = $externalExecute;
			}

			return $returnContent;
		}

		/**
		 * SOAPClient detection method (moved from primary curl executor to make it possible to detect soapcalls from other Addons)
		 *
		 * @param string $url
		 * @param array $postData
		 * @param int $CurlMethod
		 *
		 * @return MODULE_SOAP
		 * @throws \Exception
		 * @since 6.0.14
		 */
		private function executeHttpSoap( $url = '', $postData = array(), $CurlMethod = NETCURL_POST_METHODS::METHOD_GET ) {
			$this->setChain( false );
			$Soap = new MODULE_SOAP( $this->CurlURL, $this );
			$Soap->setFlag( 'IS_SOAP' );
			$Soap->setChain( false );
			$Soap->setCustomUserAgent( $this->CustomUserAgent );
			$Soap->setThrowableState( $this->canThrow );
			$Soap->setSoapAuthentication( $this->AuthData );
			$Soap->setSoapTryOnce( $this->SoapTryOnce );
			try {
				$getSoapResponse                      = $Soap->getSoap();
				$this->debugData['soapdata']['url'][] = array(
					'url'       => $this->CurlURL,
					'opt'       => $this->getCurlOptByKeys(),
					'success'   => true,
					'exception' => null,
					'previous'  => null
				);
			} catch ( \Exception $getSoapResponseException ) {
				$this->debugData['soapdata']['url'][] = array(
					'url'       => $this->CurlURL,
					'opt'       => $this->getCurlOptByKeys(),
					'success'   => false,
					'exception' => $getSoapResponseException,
					'previous'  => $getSoapResponseException->getPrevious()
				);
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception from soapClient: " . $getSoapResponseException->getMessage(), $getSoapResponseException->getCode() );
			}

			return $getSoapResponse;

		}

		/**
		 * Execution of http-calls via external Addons
		 *
		 * @param string $url
		 * @param array $postData
		 * @param int $CurlMethod
		 * @param int $postAs
		 *
		 * @return bool|MODULE_CURL|MODULE_SOAP
		 * @throws \Exception
		 * @since 6.0.14
		 */
		private function executeHttpExternal( $url = '', $postData = array(), $CurlMethod = NETCURL_POST_METHODS::METHOD_GET, $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			if ( preg_match( "/\?wsdl$|\&wsdl$/i", $this->CurlURL ) || $postAs == NETCURL_POST_DATATYPES::DATATYPE_SOAP ) {
				if ( ! $this->hasSoap() ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: SoapClient is not available in this system", $this->NETWORK->getExceptionCode( 'NETCURL_SOAPCLIENT_CLASS_MISSING' ) );
				}

				return $this->executeHttpSoap( $url, $this->PostData, $CurlMethod );
			}
			$guzDrivers = array(
				NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP,
				NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM
			);
			if ( $this->getDriver() == NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS ) {
				return $this->executeWpHttp( $url, $this->PostData, $CurlMethod, $postAs );
			} else if ( in_array( $this->getDriver(), $guzDrivers ) ) {
				return $this->executeGuzzleHttp( $url, $this->PostData, $CurlMethod, $postAs );
			} else {
				return false;
			}
		}

		/**
		 * Using WordPress curl driver to make webcalls
		 *
		 * SOAPClient is currently not supported through this interface, so this library will fall back to SimpleSoap before reaching this point if wsdl links are used
		 *
		 * @param string $url
		 * @param array $postData
		 * @param int $CurlMethod
		 * @param int $postAs
		 *
		 * @return $this
		 * @throws \Exception
		 * @since 6.0.14
		 */
		private function executeWpHttp( $url = '', $postData = array(), $CurlMethod = NETCURL_POST_METHODS::METHOD_GET, $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$parsedResponse = null;
			if ( isset( $this->Drivers[ NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS ] ) ) {
				/** @noinspection PhpUndefinedClassInspection */
				/** @var $worker \WP_Http */
				$worker = $this->Drivers[ NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS ];
			} else {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: Could not find any available transport for WordPress Driver", $this->NETWORK->getExceptionCode( 'NETCURL_WP_TRANSPORT_ERROR' ) );
			}

			if ( ! is_null( $worker ) ) {
				/** @noinspection PhpUndefinedMethodInspection */
				$transportInfo = $worker->_get_first_available_transport( array() );
			}
			if ( empty( $transportInfo ) ) {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: Could not find any available transport for WordPress Driver", $this->NETWORK->getExceptionCode( 'NETCURL_WP_TRANSPORT_ERROR' ) );
			}

			$postThis = array( 'body' => $this->PostDataReal );
			if ( $postAs == NETCURL_POST_DATATYPES::DATATYPE_JSON ) {
				$postThis['headers'] = array( "content-type" => "application-json" );
				$postThis['body']    = $this->PostData;
			}

			$wpResponse = null;
			if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_GET ) {
				/** @noinspection PhpUndefinedMethodInspection */
				$wpResponse = $worker->get( $url, $postThis );
			} else if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_POST ) {
				/** @noinspection PhpUndefinedMethodInspection */
				$wpResponse = $worker->post( $url, $postThis );
			} else if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_REQUEST ) {
				/** @noinspection PhpUndefinedMethodInspection */
				$wpResponse = $worker->request( $url, $postThis );
			}
			if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_HEAD ) {
				/** @noinspection PhpUndefinedMethodInspection */
				$wpResponse = $worker->head( $url, $postThis );
			}

			/** @noinspection PhpUndefinedClassInspection */
			/** @var $httpResponse \WP_HTTP_Requests_Response */
			$httpResponse = $wpResponse['http_response'];
			/** @noinspection PhpUndefinedClassInspection */
			/** @var $httpReponseObject \Requests_Response */
			/** @noinspection PhpUndefinedMethodInspection */
			$httpResponseObject              = $httpResponse->get_response_object();
			$rawResponse                     = $httpResponseObject->raw;
			$this->TemporaryExternalResponse = array( 'worker' => $worker, 'request' => $wpResponse );
			$this->ParseResponse( $rawResponse );

			return $this;
		}

		/**
		 * Guzzle wrapper
		 *
		 * @param string $url
		 * @param array $postData
		 * @param int $CurlMethod
		 * @param int $postAs
		 *
		 * @return $this|MODULE_CURL
		 * @throws \Exception
		 */
		private function executeGuzzleHttp( $url = '', $postData = array(), $CurlMethod = NETCURL_POST_METHODS::METHOD_GET, $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			/** @noinspection PhpUndefinedClassInspection */
			/** @noinspection PhpUndefinedNamespaceInspection */
			/** @var $gResponse \GuzzleHttp\Psr7\Response */
			$gResponse   = null;
			$rawResponse = null;
			$gBody       = null;

			$myChosenGuzzleDriver = $this->getDriver();
			/** @noinspection PhpUndefinedClassInspection */
			/** @noinspection PhpUndefinedNamespaceInspection */
			/** @var $worker \GuzzleHttp\Client */
			$worker                 = $this->Drivers[ $myChosenGuzzleDriver ];
			$postOptions            = array();
			$postOptions['headers'] = array();
			$contentType            = $this->getContentType();

			if ( $postAs === NETCURL_POST_DATATYPES::DATATYPE_JSON ) {
				$postOptions['headers']['Content-Type'] = 'application/json; charset=utf-8';
				if ( is_string( $postData ) ) {
					$jsonPostData = @json_decode( $postData );
					if ( is_object( $jsonPostData ) ) {
						$postData = $jsonPostData;
					}
				}
				$postOptions['json'] = $postData;
			} else {
				if ( is_array( $postData ) ) {
					$postOptions['form_params'] = $postData;
				}
			}

			$hasAuth = false;
			if ( isset( $this->AuthData['Username'] ) ) {
				$hasAuth = true;
				if ( $this->AuthData['Type'] == NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
					$postOptions['headers']['Accept'] = '*/*';
					if ( ! empty( $contentType ) ) {
						$postOptions['headers']['Content-Type'] = $contentType;
					}
					$postOptions['auth'] = array(
						$this->AuthData['Username'],
						$this->AuthData['Password']
					);
					//$postOptions['headers']['Authorization'] = 'Basic ' . base64_encode($this->AuthData['Username'] . ":" . $this->AuthData['Password']);
				}
			}

			if ( method_exists( $worker, 'request' ) ) {
				if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_GET ) {
					/** @noinspection PhpUndefinedMethodInspection */
					$gRequest = $worker->request( 'GET', $url, $postOptions );
				} else if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_POST ) {
					/** @noinspection PhpUndefinedMethodInspection */
					$gRequest = $worker->request( 'POST', $url, $postOptions );
				} else if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_PUT ) {
					/** @noinspection PhpUndefinedMethodInspection */
					$gRequest = $worker->request( 'PUT', $url, $postOptions );
				} else if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_DELETE ) {
					/** @noinspection PhpUndefinedMethodInspection */
					$gRequest = $worker->request( 'DELETE', $url, $postOptions );
				} else if ( $CurlMethod == NETCURL_POST_METHODS::METHOD_HEAD ) {
					/** @noinspection PhpUndefinedMethodInspection */
					$gRequest = $worker->request( 'HEAD', $url, $postOptions );
				}
			} else {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " streams for guzzle is probably missing as I can't find the request method in the current class", $this->NETWORK->getExceptionCode( 'NETCURL_GUZZLESTREAM_MISSING' ) );
			}
			/** @noinspection PhpUndefinedVariableInspection */
			$this->TemporaryExternalResponse = array( 'worker' => $worker, 'request' => $gRequest );
			/** @noinspection PhpUndefinedMethodInspection */
			$gHeaders = $gRequest->getHeaders();
			/** @noinspection PhpUndefinedMethodInspection */
			$gBody = $gRequest->getBody()->getContents();
			/** @noinspection PhpUndefinedMethodInspection */
			$statusCode = $gRequest->getStatusCode();
			/** @noinspection PhpUndefinedMethodInspection */
			$statusReason = $gRequest->getReasonPhrase();
			/** @noinspection PhpUndefinedMethodInspection */
			$rawResponse .= "HTTP/" . $gRequest->getProtocolVersion() . " " . $gRequest->getStatusCode() . " " . $gRequest->getReasonPhrase() . "\r\n";
			$rawResponse .= "X-NetCurl-ClientDriver: " . $this->getDriver() . "\r\n";
			if ( is_array( $gHeaders ) ) {
				foreach ( $gHeaders as $hParm => $hValues ) {
					$rawResponse .= $hParm . ": " . implode( "\r\n", $hValues ) . "\r\n";
				}
			}
			$rawResponse .= "\r\n" . $gBody;

			// Prevent problems during authorization. Unsupported media type checks defaults to application/json
			if ( $hasAuth && $statusCode == 415 ) {
				// Ask service for content types at first. If nothing found, run self set application/json.
				$contentTypeRequest = $gRequest->getHeader( 'content-type' );
				if ( empty( $contentTypeRequest ) ) {
					$this->setContentType();
				} else {
					$this->setContentType( $contentTypeRequest );
				}

				return $this->executeGuzzleHttp( $url, $postData, $CurlMethod, $postAs );
			}

			$this->ParseResponse( $rawResponse );
			$this->throwCodeException( $statusCode, $statusReason );

			return $this;
		}

		/**
		 * Get what external driver see
		 *
		 * @return null
		 */
		public function getExternalDriverResponse() {
			return $this->TemporaryExternalResponse;
		}

	}

	if ( ! class_exists( 'Tornevall_cURL' ) && ! class_exists( 'TorneLIB\Tornevall_cURL' ) ) {
		/**
		 * Class MODULE_CURL
		 * @package TorneLIB
		 * @throws \Exception
		 */
		class Tornevall_cURL extends MODULE_CURL {
			function __construct( $PreferredURL = '', $PreparedPostData = array(), $PreferredMethod = NETCURL_POST_METHODS::METHOD_POST, $flags = array() ) {
				return parent::__construct( $PreferredURL, $PreparedPostData, $PreferredMethod );
			}
		}
	}
}