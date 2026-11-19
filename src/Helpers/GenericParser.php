<?php /** @noinspection PhpComposerExtensionStubsInspection */

/**
 * Copyright © Thomas Tornevall / Tornevall Networks. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace TorneLIB\Helpers;

use Exception;
use TorneLIB\IO\Data\Content;
use TorneLIB\Module\Network\Wrappers\RssWrapper;

/**
 * Class GenericParser A generic parser which is shared over many classes.
 *
 * @package TorneLIB\Module\Config
 * @since 6.1.0
 */
class GenericParser
{
    /**
     * @param string $string
     * @param string $returnData
     * @return int|string
     * @since 6.1.0
     */
    public static function getHttpHead($string, $returnData = 'code')
    {
        if (!is_string($string)) {
            // Casting is probably not the right way to handle this so we'll reset it instead.
            $string = '';
        }
        $return = $string;
        $headString = preg_replace(
            '/(.*?)\sHTTP\/(.*?)\s(.*)$/is',
            '$3',
            trim($string)
        );

        if ((bool)preg_match('/\s/', $headString)) {
            $headContent = explode(' ', $headString, 2);
            // Make sure there is no extras when starting to extract this data.
            if (($returnData === 'code' && (int)$headContent[1] > 0) ||
                (
                    !is_numeric($headContent[0]) &&
                    0 === stripos($headContent[0], "http") &&
                    (bool)preg_match(
                        '/\s/',
                        $headContent[1]
                    )
                )
            ) {
                // Drop one to the left, and retry.
                $headContent = explode(' ', trim($headContent[1]), 2);
            }

            switch ($returnData) {
                case 'code':
                    if ((int)$headContent[0]) {
                        $return = (int)$headContent[0];
                    }
                    break;
                case 'message':
                    $return = isset($headContent[1]) ? (string)$headContent[1] : '';
                    break;
                default:
                    $return = $string;
                    break;
            }
        }

        return $return;
    }

    /**
     * @param $content
     * @param $contentType
     * @return array|mixed
     * @since 6.1.0
     */
    public static function getParsed($content, $contentType)
    {
        $return = $content;

        switch ($contentType) {
            case 'html':
                // @todo Use Laminas.
                break;
            case 'xml':
            case !empty($contentType) && (bool)preg_match('/\/xml|\+xml/i', $contentType):
                // Feed parsing is reserved for feed media types or an actual
                // RSS/Atom document. Generic XML must retain its legacy shape.
                if (class_exists('Laminas\\Feed\\Reader\\Reader') &&
                    (self::isFeedContentType($contentType) || self::isFeedXml($content))
                ) {
                    $return = (new RssWrapper())->getParsed($content);
                    break;
                }
                $return = (new Content())->getFromXml($content);
                break;
            case (bool)preg_match('/\/json/i', $contentType):
                // If this check is not a typecasted check, things will break bad.
                if (is_array($content)) {
                    $content = json_encode($content);
                }
                $return = json_decode($content, false);
                break;
            default:
                break;
        }

        return $return;
    }

    /**
     * Explicit feed media types may delegate parsing to Laminas Feed Reader.
     *
     * @param string $contentType
     * @return bool
     */
    private static function isFeedContentType($contentType)
    {
        return is_string($contentType) &&
            (bool)preg_match('~/(?:rss|atom)\+xml(?:\s*;|$)~i', trim($contentType));
    }

    /**
     * Inspect the XML root without fetching external entities or guessing
     * from nested elements. Only verified feed roots are delegated to Laminas.
     *
     * @param mixed $content
     * @return bool
     */
    private static function isFeedXml($content)
    {
        if (!is_string($content) || $content === '') {
            return false;
        }

        if (!class_exists('XMLReader') && !class_exists('DOMDocument')) {
            return false;
        }

        $previousErrorMode = libxml_use_internal_errors(true);
        try {
            if (class_exists('XMLReader')) {
                $reader = new \XMLReader();
                try {
                    if (!@$reader->XML($content, null, LIBXML_NONET)) {
                        return false;
                    }

                    while (@$reader->read()) {
                        if ($reader->nodeType !== \XMLReader::ELEMENT) {
                            continue;
                        }

                        if (self::isFeedRoot($reader->localName, $reader->namespaceURI)) {
                            return true;
                        }

                        // RSS 1.0 uses rdf:RDF with an RSS-namespace channel.
                        if ($reader->localName === 'RDF' &&
                            $reader->namespaceURI === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
                        ) {
                            while (@$reader->read()) {
                                if ($reader->nodeType === \XMLReader::ELEMENT &&
                                    $reader->depth === 1 &&
                                    $reader->localName === 'channel' &&
                                    $reader->namespaceURI === 'http://purl.org/rss/1.0/'
                                ) {
                                    return true;
                                }
                                if ($reader->nodeType === \XMLReader::END_ELEMENT &&
                                    $reader->depth === 0
                                ) {
                                    break;
                                }
                            }
                        }

                        return false;
                    }
                } finally {
                    $reader->close();
                }

                return false;
            }

            // DOM remains a safe fallback when the XMLReader extension is absent.
            $document = new \DOMDocument();
            if (!@$document->loadXML($content, LIBXML_NONET)) {
                return false;
            }
            $root = $document->documentElement;
            if (!$root) {
                return false;
            }
            if (self::isFeedRoot($root->localName, $root->namespaceURI)) {
                return true;
            }
            if ($root->localName === 'RDF' &&
                $root->namespaceURI === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
            ) {
                foreach ($root->childNodes as $child) {
                    if ($child instanceof \DOMElement &&
                        $child->localName === 'channel' &&
                        $child->namespaceURI === 'http://purl.org/rss/1.0/'
                    ) {
                        return true;
                    }
                }
            }

            return false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }
    }

    /**
     * @param string $name
     * @param string $namespace
     * @return bool
     */
    private static function isFeedRoot($name, $namespace)
    {
        return ($name === 'rss' && ($namespace === '' || $namespace === null)) ||
            ($name === 'feed' && (
                $namespace === 'http://www.w3.org/2005/Atom' ||
                $namespace === 'http://purl.org/atom/ns#'
            ));
    }

    /**
     * @param $name
     * @param $arguments
     * @return false|mixed
     * @throws Exception
     */
    public static function __callStatic($name, $arguments)
    {
        if (class_exists(SimpleDomParser::class) &&
            method_exists(SimpleDomParser::class, $name)
        ) {
            return call_user_func_array([SimpleDomParser::class, $name], $arguments);
        }

        if (class_exists(StructuredDomParser::class) &&
            method_exists(StructuredDomParser::class, $name)
        ) {
            return call_user_func_array([StructuredDomParser::class, $name], $arguments);
        }

        throw new Exception(
            sprintf('Call to undefined method %s::%s', self::class, $name),
            404
        );
    }
}
