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

if ( ! class_exists( 'NETCURL_POST_AS' ) && ! class_exists( 'TorneLIB\NETCURL_POST_AS' ) ) {
	/**
	 * Class CURL_POST_AS Prepared formatting for POST-content in this library (Also available from for example PUT)
	 *
	 * @package TorneLIB
	 */
	abstract class NETCURL_POST_AS {
		const POST_AS_NORMAL = 0;
		const POST_AS_JSON = 1;
		const POST_AS_SOAP = 2;
	}
}
if ( ! class_exists( 'CURL_POST_AS' ) && ! class_exists( 'TorneLIB\CURL_POST_AS' ) ) {
	/**
	 * Class CURL_POST_AS Prepared formatting for POST-content in this library (Also available from for example PUT)
	 *
	 * @package TorneLIB
	 * @deprecated Use NETCURL_POST_AS
	 */
	abstract class CURL_POST_AS extends NETCURL_POST_AS {
	}
}