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
 */

namespace TorneLIB;

if ( ! class_exists( 'NETCURL_DRIVER_GUZZLEHTTP' ) && ! class_exists( 'TorneLIB\NETCURL_DRIVER_GUZZLEHTTPINTERFACE' ) ) {
	/**
	 * Class NETCURL_DRIVERS Network communications driver detection
	 *
	 * @package TorneLIB
	 */
	class NETCURL_DRIVER_GUZZLEHTTP implements NETCURL_DRIVERS_INTERFACE {

		private $DRIVER_ID = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET;
		private $PARAMETERS = array();
		private $DRIVER;

		private $CLASS = array(
			'GuzzleHttp\Client',
			'GuzzleHttp\Handler\StreamHandler'
		);

		public function __construct( $parameters = null ) {
			if ( ! is_null( $parameters ) ) {
				$this->setParameters( $parameters );
			}
		}

		public function setDriverId( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET ) {
			$this->DRIVER_ID = $driverId;
		}

		public function setParameters( $parameters = array() ) {
			$this->PARAMETERS = $parameters;
		}
	}
}