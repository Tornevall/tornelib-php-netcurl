<?php

namespace TorneLIB\Helpers;

/**
 * Object-oriented DOM parser living beside the legacy SimpleDomParser API.
 *
 * No existing SimpleDomParser methods or return structures are changed. New
 * callers can opt into traversable models, JSON-friendly serialization and
 * semantic element discovery.
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

    /**
     * Find elements whose tag names or semantic attributes match concepts such
     * as title/headline, link/url, description/summary or date/published.
     *
     * @param string $content
     * @param string[] $names
     * @param string $format
     * @param int $limit
     * @return DomNodeCollection
     */
    public static function findElementsBySemanticNames(
        $content,
        array $names,
        $format = DomDocumentModel::FORMAT_AUTO,
        $limit = 100
    ) {
        $document = self::getDocumentModel($content, $format);

        return DomSemanticSearch::findBySemanticNames($document, $names, $limit);
    }

    /**
     * Inventory tags and common semantic attributes in a document.
     *
     * @param string $content
     * @param string $format
     * @return array
     */
    public static function getDomElementInventory($content, $format = DomDocumentModel::FORMAT_AUTO)
    {
        return DomSemanticSearch::getElementInventory(self::getDocumentModel($content, $format));
    }

    /**
     * Discover repeated structures that may represent rows/cards/items.
     *
     * @param string $content
     * @param string $format
     * @param int $minimumOccurrences
     * @param int $limit
     * @return array
     */
    public static function discoverDomStructures(
        $content,
        $format = DomDocumentModel::FORMAT_AUTO,
        $minimumOccurrences = 2,
        $limit = 25
    ) {
        return DomSemanticSearch::discoverRepeatedStructures(
            self::getDocumentModel($content, $format),
            $minimumOccurrences,
            $limit
        );
    }
}
