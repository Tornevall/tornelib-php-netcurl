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

if ( ! class_exists( 'NETCURL_PARSER' ) && ! class_exists( 'TorneLIB\NETCURL_PARSER' ) ) {
	/**
	 * Class NETCURL_PARSER Network communications driver detection
	 *
	 * @package TorneLIB
	 */
	class NETCURL_PARSER {

		private $PARSE_CONTAINER = '';
		private $PARSE_CONTENT_TYPE = '';
		private $PARSE_CONTENT_OUTPUT = '';

		/** @var TorneLIB_IO $IO */
		private $IO;

		/**
		 * NETCURL_PARSER constructor.
		 *
		 * @param string $htmlContent
		 * @param string $contentType
		 *
		 * @throws \Exception
		 * @since 6.0.0
		 */
		public function __construct( $htmlContent = '', $contentType = '' ) {
			$this->IO                   = new TorneLIB_IO();
			$this->PARSE_CONTAINER      = $htmlContent;
			$this->PARSE_CONTENT_TYPE   = $contentType;
			$this->PARSE_CONTENT_OUTPUT = $this->getContentByTest();
		}

		/**
		 * @return null|string
		 * @since 6.0.0
		 */
		private function getContentByJson() {
			try {
				return $this->getNull( $this->IO->getFromJson( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * @return null|string
		 * @since 6.0.0
		 */
		private function getContentByXml() {
			try {
				return $this->getNull( $this->IO->getFromXml( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * @return null|string
		 * @since 6.0.0
		 */
		private function getContentByYaml() {
			try {
				return $this->getNull( $this->IO->getFromYaml( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * @return null|string
		 * @since 6.0.0
		 */
		private function getContentBySerial() {
			try {
				return $this->getNull( $this->IO->getFromSerializerInternal( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * @param string $testData
		 *
		 * @return null|string
		 * @since 6.0.0
		 */
		private function getNull( $testData = '' ) {
			return empty( $testData ) ? null : $testData;
		}

		/**
		 * @return null|void
		 * @throws \Exception
		 * @since 6.0.0
		 */
		private function getContentByTest() {
			$returnNonNullValue = null;

			if ( ! is_null( $respond = $this->getContentByXml() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! is_null( $respond = $this->getContentBySerial() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! is_null( $respond = $this->getContentByJson() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! is_null( $respond = $this->getContentByYaml() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! is_null( $response = $this->getDomElements() ) ) {
				return $response;
			}

			return $returnNonNullValue;
		}


		/**
		 * Experimental: Convert DOMDocument to an array
		 *
		 * @param array $childNode
		 * @param string $getAs
		 *
		 * @return array
		 * @since 6.0.0
		 */
		private function getChildNodes( $childNode = array(), $getAs = '' ) {
			$childNodeArray      = array();
			$childAttributeArray = array();
			$childIdArray        = array();
			$returnContext       = "";
			if ( is_object( $childNode ) ) {
				/** @var \DOMNodeList $nodeItem */
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
							/** @since 6.0.20 Saving innerhtml */
							$elementData['innerhtml'] = $nodeItem->ownerDocument->saveHTML( $nodeItem );
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

							$idNoName = $nodeItem->tagName;
							// Forms without id namings will get the tagname. This will open up for reading forms and other elements without id's.
							// NOTE: If forms are not tagged with an id, the form will not render "properly" and the form fields might pop outside the real form.
							if ( empty( $elementData['id'] ) ) {
								$elementData['id'] = $idNoName;
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
		 * @return array
		 * @throws \Exception
		 * @since 6.0.0
		 */
		private function getDomElements() {
			$domContent                 = array();
			$domContent['ByNodes']      = array();
			$domContent['ByClosestTag'] = array();
			$domContent['ById']         = array();
			$hasContent                 = false;
			if ( class_exists( 'DOMDocument' ) ) {
				if ( ! empty( $this->PARSE_CONTAINER ) ) {
					$DOM = new \DOMDocument();
					libxml_use_internal_errors( true );
					$DOM->loadHTML( $this->PARSE_CONTAINER );
					if ( isset( $DOM->childNodes->length ) && $DOM->childNodes->length > 0 ) {
						$elementsByTagName = $DOM->getElementsByTagName( '*' );
						$childNodeArray    = $this->getChildNodes( $elementsByTagName );
						$childTagArray     = $this->getChildNodes( $elementsByTagName, 'tagnames' );
						$childIdArray      = $this->getChildNodes( $elementsByTagName, 'id' );
						if ( is_array( $childNodeArray ) && count( $childNodeArray ) ) {
							$domContent['ByNodes'] = $childNodeArray;
							$hasContent            = true;
						}
						if ( is_array( $childTagArray ) && count( $childTagArray ) ) {
							$domContent['ByClosestTag'] = $childTagArray;
						}
						if ( is_array( $childIdArray ) && count( $childIdArray ) ) {
							$domContent['ById'] = $childIdArray;
						}
					}
				}
			} else {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " HtmlParse exception: Can not parse DOMDocuments without the DOMDocuments class", $this->NETWORK->getExceptionCode( "NETCURL_DOMDOCUMENT_CLASS_MISSING" ) );
			}

			if ( ! $hasContent ) {
				return null;
			}

			return $domContent;
		}


		public function getParsedResponse() {
			return $this->PARSE_CONTENT_OUTPUT;
		}


	}
}