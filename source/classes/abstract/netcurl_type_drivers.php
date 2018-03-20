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

if ( ! class_exists( 'NETCURL_DRIVERS' ) && ! class_exists( 'TorneLIB\NETCURL_DRIVERS' ) ) {
	/**
	 * Class TORNELIB_CURL_ENVIRONMENT
	 *
	 * The unit testing helper. To not collide with production environments, somet settings should only be available while unit testing.
	 *
	 * @package TorneLIB
	 */
	abstract class NETCURL_DRIVERS {
		const DRIVER_NOT_SET = 0;
		const DRIVER_INTERNAL = 1;
		const DRIVER_WORDPRESS = 1000;
		const DRIVER_GUZZLEHTTP = 1001;
		const DRIVER_GUZZLEHTTP_STREAM = 1002;
	}
}
if ( ! class_exists( 'TORNELIB_CURL_DRIVERS' ) && ! class_exists( 'TorneLIB\TORNELIB_CURL_DRIVERS' ) ) {
	/**
	 * Class TORNELIB_CURL_ENVIRONMENT
	 *
	 * The unit testing helper. To not collide with production environments, somet settings should only be available while unit testing.
	 *
	 * @package TorneLIB
	 * @deprecated Use NETCURL_DRIVERS
	 */
	abstract class TORNELIB_CURL_DRIVERS extends NETCURL_DRIVERS {
	}
}