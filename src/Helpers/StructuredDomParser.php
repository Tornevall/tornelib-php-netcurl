<?php

namespace TorneLIB\Helpers;

/**
 * Object-oriented DOM parser living beside the legacy SimpleDomParser API.
 *
 * No existing SimpleDomParser methods or return structures are changed. New
 * callers can opt into traversable models and JSON-friendly serialization.
 *
 * @since 6.1.11
 */
class StructuredDomParser
{
    /**
     * Parse XML or HTML into a traversable document model.
     *
     * @param string $content
     * @param string $format auto, xml or html
     * @return DomDocumentModel
     */
    public static function getDocumentModel($content, $format = DomDocumentModel::FORMAT_AUTO)
    {
        return DomDocumentModel::fromContent($content, $format);
    }

    /**
     * Parse content and return XPath matches as a traversable collection.
     *
     * @param string $content
     * @param string $xpath
     * @param string $format auto, xml or html
     * @param array $namespaces prefix => namespace URI
     * @return DomNodeCollection
     */
    public static function getModelsFromXPath(
        $content,
        $xpath,
        $format = DomDocumentModel::FORMAT_AUTO,
        array $namespaces = []
    ) {
        return self::getDocumentModel($content, $format)->query($xpath, $namespaces);
    }
}
