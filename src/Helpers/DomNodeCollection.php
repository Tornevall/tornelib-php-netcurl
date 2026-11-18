<?php

namespace TorneLIB\Helpers;

/**
 * Traversable collection of DOM node models.
 *
 * This class intentionally lives beside the legacy XPath array API so callers
 * can opt into object traversal without changing existing integrations.
 *
 * @since 6.1.11
 */
class DomNodeCollection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var DomNodeModel[] */
    private $nodes = [];

    /**
     * @param DomNodeModel[] $nodes
     */
    public function __construct(array $nodes = [])
    {
        foreach ($nodes as $node) {
            if (!$node instanceof DomNodeModel) {
                throw new \InvalidArgumentException('DomNodeCollection only accepts DomNodeModel instances.');
            }
        }

        $this->nodes = array_values($nodes);
    }

    /**
     * @return DomNodeModel|null
     */
    public function first()
    {
        return isset($this->nodes[0]) ? $this->nodes[0] : null;
    }

    /**
     * @return DomNodeModel[]
     */
    public function all()
    {
        return $this->nodes;
    }

    /**
     * Convert all nodes into JSON-friendly values.
     *
     * @return array
     */
    public function toArray()
    {
        $return = [];

        foreach ($this->nodes as $node) {
            $return[] = $node->toArray();
        }

        return $return;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->nodes);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->nodes);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->nodes[$offset]);
    }

    /**
     * @param mixed $offset
     * @return DomNodeModel|null
     */
    public function offsetGet($offset)
    {
        return isset($this->nodes[$offset]) ? $this->nodes[$offset] : null;
    }

    /**
     * Collections returned by the parser are immutable snapshots.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('DomNodeCollection is immutable.');
    }

    /**
     * Collections returned by the parser are immutable snapshots.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('DomNodeCollection is immutable.');
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
