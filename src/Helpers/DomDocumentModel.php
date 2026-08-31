<?php

namespace TorneLIB\Helpers;

/**
 * Parsed XML/HTML document with traversable node models and XPath support.
 *
 * @since 6.1.11
 */
class DomDocumentModel implements \IteratorAggregate, \JsonSerializable
{
    const FORMAT_AUTO = 'auto';
    const FORMAT_XML = 'xml';
    const FORMAT_HTML = 'html';

    /** @var \DOMDocument */
    private $document;

    /** @var DomNodeModel */
    private $root;

    /** @var string */
    private $format;

    /**
     * @param \DOMDocument $document
     * @param string $format
     */
    private function __construct(\DOMDocument $document, $format)
    {
        if (!$document->documentElement instanceof \DOMElement) {
            throw new \RuntimeException('Parsed document has no root element.');
        }

        $this->document = $document;
        $this->format = $format;
        $this->root = DomNodeModel::fromDomNode($document->documentElement);
    }

    /**
     * Parse XML or HTML into a document model.
     *
     * @param string $content
     * @param string $format auto, xml or html
     * @return self
     */
    public static function fromContent($content, $format = self::FORMAT_AUTO)
    {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('DOM content must be a string.');
        }

        if (trim($content) === '') {
            throw new \RuntimeException('Unable to parse DOM content: input is empty.');
        }

        $format = strtolower((string)$format);
        if (!in_array($format, [self::FORMAT_AUTO, self::FORMAT_XML, self::FORMAT_HTML], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported DOM format: %s', $format));
        }

        if ($format === self::FORMAT_AUTO) {
            $format = self::detectFormat($content);
        }

        $document = self::loadDocument($content, $format);

        return new self($document, $format);
    }

    /**
     * @return DomNodeModel
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Query the parsed document and return traversable node models.
     *
     * XML namespaces can be registered explicitly. Namespace declarations on
     * the document root are also discovered automatically. A default namespace
     * is exposed through the synthetic prefix "default".
     *
     * @param string $xpath
     * @param array $namespaces prefix => namespace URI
     * @return DomNodeCollection
     */
    public function query($xpath, array $namespaces = [])
    {
        $finder = new \DOMXPath($this->document);
        $registered = $this->getNamespaces();

        foreach ($namespaces as $prefix => $namespace) {
            $registered[$prefix] = $namespace;
        }

        foreach ($registered as $prefix => $namespace) {
            if ($prefix === '' || $namespace === '') {
                continue;
            }
            $finder->registerNamespace($prefix, $namespace);
        }

        $result = @$finder->query((string)$xpath);
        if ($result === false) {
            throw new \InvalidArgumentException(sprintf('Invalid XPath query: %s', $xpath));
        }

        $nodes = [];
        foreach ($result as $node) {
            if ($node instanceof \DOMNode) {
                $nodes[] = DomNodeModel::fromDomNode($node);
            }
        }

        return new DomNodeCollection($nodes);
    }

    /**
     * Discover namespaces available on the document root.
     *
     * Namespace declarations are not normal DOM attributes in PHP, so use the
     * XPath namespace axis instead of iterating DOMElement::attributes. The
     * built-in XML namespace is intentionally excluded. The default namespace
     * is mapped to the synthetic prefix "default".
     *
     * @return array
     */
    public function getNamespaces()
    {
        return self::discoverNamespaces($this->document);
    }

    /**
     * Convert the document into an object-like array while preserving the root
     * element name.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            $this->root->getName() => $this->root->toArray(),
        ];
    }

    /**
     * Iterate the root node's direct children.
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return $this->root->getIterator();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param string $content
     * @return string
     */
    private static function detectFormat($content)
    {
        $trimmed = ltrim($content);

        if (preg_match('/^<\?xml\b/i', $trimmed)) {
            return self::FORMAT_XML;
        }

        if (preg_match('/^<!doctype\s+html\b/i', $trimmed) || preg_match('/^<html\b/i', $trimmed)) {
            return self::FORMAT_HTML;
        }

        // Well-formed custom XML does not always have an XML declaration. Try
        // XML first, but only for auto detection; malformed markup falls back
        // to the forgiving HTML parser. Ambiguous HTML fragments can be forced
        // to HTML by passing FORMAT_HTML explicitly.
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $isXml = false;

        try {
            $testDocument = new \DOMDocument();
            $isXml = $testDocument->loadXML($content, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $isXml ? self::FORMAT_XML : self::FORMAT_HTML;
    }

    /**
     * @param string $content
     * @param string $format
     * @return \DOMDocument
     */
    private static function loadDocument($content, $format)
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $errors = [];
        $loaded = false;
        $document = new \DOMDocument();

        try {
            if ($format === self::FORMAT_XML) {
                $loaded = $document->loadXML($content, LIBXML_NONET);
            } else {
                $loaded = $document->loadHTML($content, LIBXML_NONET);
            }

            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded) {
            $message = 'Unable to parse DOM content.';
            if (isset($errors[0]) && isset($errors[0]->message)) {
                $message = trim($errors[0]->message);
            }

            throw new \RuntimeException($message);
        }

        return $document;
    }

    /**
     * @param \DOMDocument $document
     * @return array
     */
    private static function discoverNamespaces(\DOMDocument $document)
    {
        $return = [];
        $root = $document->documentElement;

        if (!$root instanceof \DOMElement) {
            return $return;
        }

        $finder = new \DOMXPath($document);
        $namespaceNodes = $finder->query('namespace::*', $root);
        if ($namespaceNodes === false) {
            return $return;
        }

        foreach ($namespaceNodes as $namespaceNode) {
            $namespace = (string)$namespaceNode->nodeValue;
            if ($namespace === '' || $namespace === 'http://www.w3.org/XML/1998/namespace') {
                continue;
            }

            $nodeName = (string)$namespaceNode->nodeName;
            $localName = isset($namespaceNode->localName) ? (string)$namespaceNode->localName : '';
            $prefix = isset($namespaceNode->prefix) ? (string)$namespaceNode->prefix : '';

            if ($nodeName === 'xmlns' || ($prefix === '' && $localName === 'xmlns')) {
                $return['default'] = $namespace;
                continue;
            }

            if (strpos($nodeName, 'xmlns:') === 0) {
                $return[substr($nodeName, 6)] = $namespace;
                continue;
            }

            if ($prefix === 'xmlns' && $localName !== '') {
                $return[$localName] = $namespace;
                continue;
            }

            if ($nodeName !== '' && $nodeName !== 'xml') {
                $return[$nodeName] = $namespace;
            }
        }

        return $return;
    }
}
