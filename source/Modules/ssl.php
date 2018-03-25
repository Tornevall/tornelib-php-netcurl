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
 * @version 6.0.0
 */

namespace TorneLIB;

/**
 * Class MODULE_SSL SSL Helper class
 * @package TorneLIB
 */
class MODULE_SSL {
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
	private $sslDriverError = array();
	/** @var bool If SSL has been compiled in CURL, this will transform to true */
	private $sslCurlDriver = false;

	/** @var array Default paths to the certificates we are looking for */
	private $sslPemLocations = array( '/etc/ssl/certs' );
	/** @var array Files to look for in sslPemLocations */
	private $sslPemFiles = array( 'cacert.pem', 'ca-certificates.crt' );
	/** @var string Location of the SSL certificate bundle */
	private $sslRealCertLocation;
	/** @var bool Strict verification of the connection (sslVerify) */
	private $SSL_STRICT_VERIFICATION = true;
	/** @var null|bool Allow self signed certificates */
	private $SSL_STRICT_SELF = null;
	/** @var bool Allowing fallback to unstict verification */
	private $SSL_UNSTRICT_FALLBACK = false;

	/** @var MODULE_CURL $PARENT */
	private $PARENT;

	/**
	 * MODULE_SSL constructor.
	 *
	 * @param MODULE_CURL $MODULE_CURL
	 */
	function __construct( $MODULE_CURL = null ) {
		if ( is_object( $MODULE_CURL ) ) {
			$this->PARENT = $MODULE_CURL;
		}
	}

	/**
	 * @return array
	 * @since 6.0.0
	 */
	public static function getCurlSslAvailable() {
		// Common ssl checkers (if they fail, there is a sslDriverError to recall

		$sslDriverError = array();
		if ( ! in_array( 'https', @stream_get_wrappers() ) ) {
			$sslDriverError[] = "SSL Failure: HTTPS wrapper can not be found";
		}
		if ( ! extension_loaded( 'openssl' ) ) {
			$sslDriverError[] = "SSL Failure: HTTPS extension can not be found";
		}

		if ( function_exists( 'curl_version' ) ) {
			$curlVersionRequest = curl_version();
			$curlVersion        = $curlVersionRequest['version'];
			if ( defined( 'CURL_VERSION_SSL' ) ) {
				if ( isset( $curlVersionRequest['features'] ) ) {
					$CURL_SSL_AVAILABLE = ( $curlVersionRequest['features'] & CURL_VERSION_SSL ? true : false );
					if ( ! $CURL_SSL_AVAILABLE ) {
						$sslDriverError[] = 'SSL Failure: Protocol "https" not supported or disabled in libcurl';
					}
				} else {
					$sslDriverError[] = "SSL Failure: CurlVersionFeaturesList does not return any feature (this should not be happen)";
				}
			}
		}

		return $sslDriverError;
	}

	/**
	 * openssl_guess rewrite
	 *
	 * @return string
	 * @since 6.0.0
	 */
	public function getSslCertificateBundle() {
		// Assume that sysadmins can handle this, if open_basedir is set as things will fail if we proceed here
		if ( ini_get( 'open_basedir' ) != '' ) {
			return '';
		}

		foreach ( $this->sslPemLocations as $filePath ) {
			if ( is_dir( $filePath ) && ! in_array( $filePath, $this->sslPemLocations ) ) {
				$this->sslPemLocations[] = $filePath;
			}
		}

		// If PHP >= 5.6.0, the OpenSSL module has its own way getting certificate locations
		if ( version_compare( PHP_VERSION, "5.6.0", ">=" ) && function_exists( "openssl_get_cert_locations" ) ) {
			$internalCheck = openssl_get_cert_locations();
			if ( isset( $internalCheck['default_cert_dir'] ) && is_dir( $internalCheck['default_cert_dir'] ) && ! empty( $internalCheck['default_cert_file'] ) ) {
				$certFile = basename( $internalCheck['default_cert_file'] );
				if ( ! in_array( $internalCheck['default_cert_dir'], $this->sslPemLocations ) ) {
					$this->sslPemLocations[] = $internalCheck['default_cert_dir'];
				}
				if ( ! in_array( $certFile, $this->sslPemFiles ) ) {
					$this->sslPemFiles[] = $certFile;
				}
			}
		}

		// get first match
		foreach ( $this->sslPemLocations as $location ) {
			foreach ( $this->sslPemFiles as $file ) {
				$fullCertPath = $location . "/" . $file;
				if ( file_exists( $fullCertPath ) && empty( $this->sslRealCertLocation ) ) {
					$this->sslRealCertLocation = $fullCertPath;
				}
			}
		}

		return $this->sslRealCertLocation;
	}

	/**
	 * @param array $pemLocationData
	 *
	 * @since 6.0.20
	 */
	public function setPemLocation( $pemLocationData = array() ) {

	}

	public function getPemLocations() {
		return $this->sslPemLocations;
	}

	/**
	 * @param bool $strictifyAllVerifications
	 * @param bool $strictifySelfSigned
	 *
	 * @since 6.0.0
	 */
	public function setStrictVerification( $strictifyAllVerifications = true, $strictifySelfSigned = true ) {
		$this->SSL_STRICT_VERIFICATION = $strictifyAllVerifications;
		$this->SSL_STRICT_SELF         = $strictifySelfSigned;
	}

	/**
	 * Returns the mode of strict verification set up. If true, netcurl will be very strict with all certificate verifications.
	 *
	 * @return bool
	 * @since 6.0.0
	 */
	public function getStrictVerification() {
		return $this->SSL_STRICT_VERIFICATION;
	}

	/**
	 * Replacement for allowSslUnverified setup
	 *
	 * @param bool $unstrictifyVerification *
	 *
	 * @since 6.0.0
	 */
	public function setSslUnstrictFallback( $unstrictifyVerification = false ) {
		$this->SSL_UNSTRICT_FALLBACK = $unstrictifyVerification;
	}

	/**
	 * @return bool
	 * @since 6.0.0
	 */
	public function getSslUnstrictFallback() {
		return $this->SSL_UNSTRICT_FALLBACK;
	}

	/**
	 * Prepare context stream for SSL
	 *
	 * @return array
	 *
	 * @since 6.0.0
	 */
	public function getSslStreamContext() {
		return array(
			'cafile'            => $this->getSslCertificateBundle(),
			'verify_peer'       => $this->SSL_STRICT_VERIFICATION,
			'verify_peer_name'  => $this->SSL_STRICT_VERIFICATION,
			'verify_host'       => $this->SSL_STRICT_VERIFICATION,
			'allow_self_signed' => $this->SSL_STRICT_SELF
		);
	}

	/**
	 * Put the context into stream for SSL
	 *
	 * @param array $optionsArray
	 * @param array $addonContextData
	 *
	 * @return array
	 * @since 6.0.0
	 */
	public function getSslStream( $optionsArray = array(), $addonContextData = array() ) {
		$streamContextOptions = array();
		if ( is_object( $this->PARENT ) ) {
			$streamContextOptions['http'] = array(
				"user_agent" => $this->PARENT->getUserAgent()
			);
		}
		$sslCorrection = $this->getSslStreamContext();
		if ( count( $sslCorrection ) ) {
			$streamContextOptions['ssl'] = $this->getSslStreamContext();
		}
		if ( is_array( $addonContextData ) && count( $addonContextData ) ) {
			foreach ( $addonContextData as $contextKey => $contextValue ) {
				$streamContextOptions[ $contextKey ] = $contextValue;
			}
		}
		$optionsArray['stream_context'] = stream_context_create( $streamContextOptions );
		$this->sslopt                   = $optionsArray;

		return $optionsArray;

	}
}