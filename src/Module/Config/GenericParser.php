<?php
/**
 * Copyright © Tomas Tornevall / Tornevall Networks. All rights reserved.
 * See LICENSE for license details.
 */

namespace TorneLIB\Module\Config;

use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Content;

/**
 * Class GenericParser A generic parser which is shared over many classes.
 *
 * @package TorneLIB\Module\Config
 * @since 6.1.0
 */
class GenericParser
{
    /**
     * @param $string
     * @param string $returnData
     * @return int|string
     * @since 6.1.0
     */
    public static function getHttpHead($string, $returnData = 'code')
    {
        $return = $string;
        $headString = preg_replace(
            '/(.*?)\sHTTP\/(.*?)\s(.*)$/is',
            '$3',
            trim($string)
        );

        if (preg_match('/\s/', $headString)) {
            $headContent = explode(' ', $headString, 2);

            // Make sure there is no extras when starting to extract this data.
            if (!is_numeric($headContent[0]) &&
                preg_match('/^http/i', $headContent[0]) &&
                preg_match('/\s/', $headContent[1]) || (
                    $returnData === 'code' &&
                    intval($headContent[1]) > 0
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
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public static function getParsed($content, $contentType)
    {
        $return = $content;

        switch ($contentType) {
            case (!empty($contentType) && preg_match('/\/xml|\+xml/i', $contentType) ? true : false):
                $return = (new Content())->getFromXml($content);
                break;
            case (preg_match('/\/json/i', $contentType) ? true : false):
                $return = json_decode($content);
                break;
            default:
                break;
        }

        return $return;
    }

}
