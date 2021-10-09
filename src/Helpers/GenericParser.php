<?php /** @noinspection PhpComposerExtensionStubsInspection */

/**
 * Copyright © Tomas Tornevall / Tornevall Networks. All rights reserved.
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
     * @param array $domListData
     * @param array $elements
     * @param array $extractKeys
     * @return array
     * @since 6.1.5
     */
    public static function getElementsByXPath($domListData, $elements, $extractKeys = [])
    {
        if (isset($domListData['domList'])) {
            $domList = $domListData['domList'];
        } else {
            $domList = $domListData;
        }
        $return = [];
        if (isset($domList) && count($domList)) {
            foreach ($domList as $domItemIndex => $domItem) {
                foreach ($elements as $elementName => $elementInformation) {
                    if ($extractedSubPath = GenericParser::getBySubXPath($domItem, $elementInformation)) {
                        $mainNode = $extractedSubPath['mainNode'];
                        $subNode = $extractedSubPath['subNode'];
                        if (is_array($extractKeys) && count($extractKeys)) {
                            $newExtraction = $extractedSubPath;
                            $newExtraction['mainNode'] = [];
                            $newExtraction['subNode'] = [];
                            foreach ($extractKeys as $extractKey) {
                                try {
                                    switch ($extractKey) {
                                        case 'value':
                                            $newExtraction['mainNode'][$extractKey] = isset($mainNode->nodeValue) ? trim($mainNode->nodeValue) : null;
                                            if (isset($subNode['node']->nodeValue)) {
                                                $newExtraction['subNode'][$extractKey] = trim($subNode['node']->nodeValue);
                                            } else {
                                                if (method_exists(
                                                        $subNode['node'],
                                                        'item'
                                                    ) && $subNode['node']->count()
                                                ) {
                                                    $subNodeItem = $subNode['node']->item(0);
                                                    $newExtraction['subNode'][$extractKey] = trim($subNodeItem->nodeValue);
                                                } else {
                                                    $newExtraction['subNode'][$extractKey] = null;
                                                }
                                            }
                                            break;
                                        default:
                                            if (method_exists($mainNode, 'getAttribute')) {
                                                $newExtraction['mainNode'][$extractKey] = trim($mainNode->getAttribute($extractKey));
                                            } else {
                                                $newExtraction['mainNode'][$extractKey] = null;
                                            }
                                            // Extract first item from subnode if it exists or nullify the data.
                                            if (method_exists($subNode['node'], 'item') &&
                                                $subNode['node']->count()
                                            ) {
                                                $subNodeItem = $subNode['node']->item(0);
                                                $newExtraction['subNode'][$extractKey] = trim($subNodeItem->getAttribute($extractKey));
                                            } else {
                                                $newExtraction['subNode'][$extractKey] = null;
                                            }
                                    }
                                } catch (Exception $e) {
                                    // Do not store and return failures.
                                    $return['errors'][] = [
                                        'code' => $e->getCode(),
                                        'message' => $e->getMessage(),
                                    ];
                                }
                                $return[$domItemIndex][$elementName] = $newExtraction;
                            }
                        } else {
                            $return[$elementName][] = $extractedSubPath;
                        }
                    }
                }
            }
        }
        return $return;
    }

    /**
     * @param array $domItem
     * @param string $subXpath
     * @since 6.1.5
     */
    public static function getBySubXPath($domItem, $subXpath)
    {
        /** @var \DOMElement $useNode */
        $useNode = $domItem['node'];

        if (method_exists($useNode, 'item')) {
            /** @var \DOMNodeList $mainNode */
            $mainNode = $useNode->item(0);
        } else {
            $mainNode = $useNode;
        }

        $subNodeItem = self::getFromExtendedXpath(
            $subXpath,
            $domItem['path'],
            $domItem['domDocument']
        );

        $return = [
            'mainNode' => $mainNode,
            'subNode' => $subNodeItem,
            'path' => method_exists($mainNode, 'getNodePath') ? $mainNode->getNodePath() : null,
        ];

        return $return;
    }

    /**
     * @param $xpath
     * @param $currentPath
     * @param \DOMDocument $domDoc
     * @return array
     * @since 6.1.5
     */
    private static function getFromExtendedXpath($xpath, $currentPath, $domDoc)
    {
        $finder = new \DOMXPath($domDoc);
        if (is_array($xpath)) {
            $currentPath .= implode('', $xpath);
        } else {
            $currentPath .= $xpath;
        }
        /** @var \DOMNodeList $queryResult */
        $queryResult = $finder->query($currentPath);
        return [
            'domDocument' => $domDoc,
            'path' => $currentPath,
            'node' => $queryResult,
        ];
    }

    /**
     * @param $xPath
     * @param $fromElementRequestArray
     * @return mixed
     * @throws Exception
     * @since 6.1.5
     */
    public static function getValuesFromXPath($xPath, $fromElementRequestArray)
    {
        $return = null;
        if (!is_array($fromElementRequestArray) || !count($fromElementRequestArray)) {
            throw new Exception(sprintf('%s Exception: Not a valid array path', __FUNCTION__), 404);
        }
        foreach ($fromElementRequestArray as $followKey) {
            $xPath = $xPath[$followKey];
        }
        return $xPath;
    }

    /**
     * @param $htmlString
     * @param $xpath
     * @return array
     * @since 6.1.5
     */
    public static function getFromXPath($htmlString, $xpath)
    {
        libxml_use_internal_errors(true);
        return self::getDataFromXPath($htmlString, $xpath);
    }

    /**
     * Extract DOMDocument data by xpath.
     * @param $html
     * @param $xpath
     * @return array
     * @since 6.1.5
     */
    private static function getDataFromXPath($html, $xpath)
    {
        $domDoc = new \DOMDocument();
        $domDoc->loadHTML($html);
        $return = [
            'domDocument' => $domDoc,
            'domList' => [],
        ];

        // If request is based on an array, this request will be transformed into recursive scanning.
        //$useXpath = is_array($xpath) ? array_shift($xpath) : $xpath;
        if (is_array($xpath)) {
            foreach ($xpath as $xPathItem) {
                $return = self::getXPathDataExtracted($domDoc, $xPathItem, $return);
            }
        } else {
            $return = self::getXPathDataExtracted($domDoc, $xpath, $return);
        }

        return $return;
    }

    /**
     * @param $domDoc
     * @param $xpath
     * @param $return
     * @return array
     * @since 6.1.5
     */
    private static function getXPathDataExtracted($domDoc, $xpath, $return)
    {
        try {
            $finder = new \DOMXPath($domDoc);
            $nodeList = $finder->query($xpath);
            $return['domDocument'] = $domDoc;

            if (!empty($nodeList) || (method_exists($nodeList, 'count') && $nodeList->count() > 0)) {
                /** @var \DOMNodeList $nodeList */
                for ($nodeIndex = 0; $nodeIndex < $nodeList->count(); $nodeIndex++) {
                    try {
                        /** @var \DOMElement $nodeItem */
                        $nodeItem = $nodeList->item($nodeIndex);
                        if (is_array($xpath)) {
                            $return['domList'][] = self::getFromExtendedXpath(
                                $xpath,
                                $nodeItem->getNodePath(),
                                $domDoc
                            );
                        } else {
                            $return['domList'][] = [
                                'domDocument' => $domDoc,
                                'path' => $nodeItem->getNodePath(),
                                'node' => $nodeItem,
                            ];
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        } catch (Exception $e) {
        }
        return (array)$return;
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
            case !empty($contentType) && (bool)preg_match('/\/xml|\+xml/i', $contentType):
                // More detection possibilites.
                /* <?xml version="1.0" encoding="UTF-8"?><rss version="2.0"*/

                // If Laminas is available, prefer that engine before simple xml.
                if ((bool)preg_match('/\/xml|\+xml/i', $contentType) && class_exists('Laminas\Feed\Reader\Reader')) {
                    $return = (new RssWrapper())->getParsed($content);
                    break;
                }
                $return = (new Content())->getFromXml($content);
                break;
            case (bool)preg_match('/\/json/i', $contentType):
                // If this check is not a typecasted check, things will break bad.
                if (is_array($content)) {
                    // Did we get bad content?
                    $content = json_encode($content);
                }
                $return = json_decode($content, false);
                break;
            default:
                break;
        }

        return $return;
    }
}
