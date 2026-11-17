<?php

namespace TorneLIB\Helpers;

/**
 * JSON-friendly, traversable representation of a DOM node.
 *
 * Child lookup always returns the first matching child. Use children() when a
 * name may occur more than once. This keeps traversal predictable while still
 * allowing repeated XML/HTML elements to be represented as collections.
 *
 * @since 6.1.11
 */
class DomNodeModel implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var string */
    private $name;

    /** @var string|null */
    private $value;

    /** @var array */
    private $attributes = [];

    /** @var DomNodeModel[] */
    private $children = [];

    /** @var \DOMNode|null */
    private $domNode;

    /**
     * @param string $name
     * @param string|null $value
     * @param array $attributes
     * @param DomNodeModel[] $children
     * @param \DOMNode|null $domNode
     */
    public function __construct($name, $value = null, array $attributes = [], array $children = [], $domNode = null)
    {
        foreach ($children as $child) {
            if (!$child instanceof self) {
                throw new \InvalidArgumentException('DomNodeModel children must be DomNodeModel instances.');
            }
        }

        if ($domNode !== null && !$domNode instanceof \DOMNode) {
            throw new \InvalidArgumentException('DomNodeModel source node must be a DOMNode instance.');
        }

        $this->name = (string)$name;
        $this->value = $value === null ? null : (string)$value;
        $this->attributes = $attributes;
        $this->children = array_values($children);
        $this->domNode = $domNode;
    }

    /**
     * Build a model recursively from a native DOM node.
     *
     * @param \DOMNode $node
     * @return self
     */
    public static function fromDomNode(\DOMNode $node)
    {
        $attributes = [];
        if ($node instanceof \DOMElement && $node->hasAttributes()) {
            foreach ($node->attributes as $attribute) {
                $attributes[$attribute->nodeName] = $attribute->nodeValue;
            }
        }

        $children = [];
        $textParts = [];

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                if ($childNode->nodeType === XML_ELEMENT_NODE) {
                    $children[] = self::fromDomNode($childNode);
                    continue;
                }

                if ($childNode->nodeType === XML_TEXT_NODE || $childNode->nodeType === XML_CDATA_SECTION_NODE) {
                    $textParts[] = $childNode->nodeValue;
                }
            }
        } elseif ($node->nodeValue !== null) {
            $textParts[] = $node->nodeValue;
        }

        $value = trim(implode('', $textParts));
        if ($value === '') {
            $value = null;
        }

        return new self($node->nodeName, $value, $attributes, $children, $node);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Direct text value belonging to this node.
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * Return the first child matching a node name.
     *
     * @param string $name
     * @return self|null
     */
    public function get($name)
    {
        foreach ($this->children as $child) {
            if ($child->getName() === $name) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Return child nodes, optionally filtered by node name.
     *
     * @param string|null $name
     * @return DomNodeCollection
     */
    public function children($name = null)
    {
        if ($name === null) {
            return new DomNodeCollection($this->children);
        }

        $nodes = [];
        foreach ($this->children as $child) {
            if ($child->getName() === $name) {
                $nodes[] = $child;
            }
        }

        return new DomNodeCollection($nodes);
    }

    /**
     * Run an XPath query relative to this node.
     *
     * This is intentionally separate from get()/children(): simple XML/RSS can
     * use object traversal, while irregular HTML and namespaced XML can keep
     * using XPath without falling back to the legacy rendered-array API.
     *
     * @param string $xpath
     * @param array $namespaces prefix => namespace URI
     * @return DomNodeCollection
     */
    public function query($xpath, array $namespaces = [])
    {
        if (!$this->domNode instanceof \DOMNode) {
            throw new \LogicException('Relative XPath requires a model created from a DOM node.');
        }

        $document = $this->domNode instanceof \DOMDocument ? $this->domNode : $this->domNode->ownerDocument;
        if (!$document instanceof \DOMDocument) {
            throw new \LogicException('Relative XPath requires an owner DOMDocument.');
        }

        $finder = new \DOMXPath($document);
        $registered = self::getDocumentNamespaces($document);

        foreach ($namespaces as $prefix => $namespace) {
            $registered[$prefix] = $namespace;
        }

        foreach ($registered as $prefix => $namespace) {
            if ($prefix === '' || $namespace === '') {
                continue;
            }
            $finder->registerNamespace($prefix, $namespace);
        }

        $result = @$finder->query((string)$xpath, $this->domNode);
        if ($result === false) {
            throw new \InvalidArgumentException(sprintf('Invalid XPath query: %s', $xpath));
        }

        $nodes = [];
        foreach ($result as $node) {
            if ($node instanceof \DOMNode) {
                $nodes[] = self::fromDomNode($node);
            }
        }

        return new DomNodeCollection($nodes);
    }

    /**
     * Return the first model from a relative XPath query.
     *
     * @param string $xpath
     * @param array $namespaces
     * @return self|null
     */
    public function queryFirst($xpath, array $namespaces = [])
    {
        return $this->query($xpath, $namespaces)->first();
    }

    /**
     * Convert the node into a compact JSON-friendly structure.
     *
     * Attributes are stored in _attributes. Direct text is stored in _value
     * when the node also has attributes or child elements. A simple leaf node
     * is represented by its scalar text value.
     *
     * @return mixed
     */
    public function toArray()
    {
        if (!count($this->children) && !count($this->attributes)) {
            return $this->value;
        }

        $return = [];

        if (count($this->attributes)) {
            $return['_attributes'] = $this->attributes;
        }

        if ($this->value !== null) {
            $return['_value'] = $this->value;
        }

        $grouped = [];
        foreach ($this->children as $child) {
            $grouped[$child->getName()][] = $child;
        }

        foreach ($grouped as $name => $nodes) {
            if (count($nodes) === 1) {
                $return[$name] = $nodes[0]->toArray();
                continue;
            }

            $return[$name] = [];
            foreach ($nodes as $node) {
                $return[$name][] = $node->toArray();
            }
        }

        return $return;
    }

    /**
     * Convenience property traversal, e.g. $node->channel->title.
     *
     * @param string $name
     * @return self|null
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->get($name) !== null;
    }

    /**
     * Iterate direct child nodes in source order.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * Count direct child nodes.
     *
     * @return int
     */
    public function count()
    {
        return count($this->children);
    }

    /**
     * Array access is an alias for get().
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->get((string)$offset) !== null;
    }

    /**
     * @param mixed $offset
     * @return self|null
     */
    public function offsetGet($offset)
    {
        return $this->get((string)$offset);
    }

    /**
     * Models returned by the parser are immutable snapshots.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('DomNodeModel is immutable.');
    }

    /**
     * Models returned by the parser are immutable snapshots.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('DomNodeModel is immutable.');
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Discover namespaces available on the document root.
     *
     * @param \DOMDocument $document
     * @return array
     */
    private static function getDocumentNamespaces(\DOMDocument $document)
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
