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
 * @version 6.0.6
 */

namespace TorneLIB;

/**
 * Class MODULE_SSL SSL Helper class
 * @package TorneLIB
 */
abstract class MODULE_SSL {
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
			$curlVersion  = $curlVersionRequest['version'];
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
}