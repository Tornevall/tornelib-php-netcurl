<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\DomNodeCollection;
use TorneLIB\Helpers\DomNodeModel;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class structuredDomErrorTest extends TestCase
{
    /** @testdox Non-string document content is rejected. */
    public function testRejectsNonStringContent()
    {
        $this->expectException(InvalidArgumentException::class);
        GenericParser::getDocumentModel(['not', 'markup']);
    }

    /** @testdox Unsupported parser formats are rejected explicitly. */
    public function testRejectsUnsupportedFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        GenericParser::getDocumentModel('<root/>', 'yaml');
    }

    /** @testdox Invalid XML fails instead of silently falling back when XML mode is explicit. */
    public function testInvalidExplicitXmlThrows()
    {
        $this->expectException(RuntimeException::class);
        GenericParser::getDocumentModel('<root><broken></root>', 'xml');
    }

    /** @testdox Empty XML fails instead of producing an empty model. */
    public function testEmptyExplicitXmlThrows()
    {
        $this->expectException(RuntimeException::class);
        GenericParser::getDocumentModel('', 'xml');
    }

    /** @testdox Invalid document XPath expressions are rejected. */
    public function testInvalidDocumentXpathThrows()
    {
        $document = GenericParser::getDocumentModel('<root><item/></root>', 'xml');

        $this->expectException(InvalidArgumentException::class);
        $document->query('//*[');
    }

    /** @testdox Invalid relative XPath expressions are rejected. */
    public function testInvalidRelativeXpathThrows()
    {
        $item = GenericParser::getModelsFromXPath('<root><item/></root>', '//item', 'xml')->first();

        $this->expectException(InvalidArgumentException::class);
        $item->query('./*[');
    }

    /** @testdox Relative XPath is unavailable on manually constructed detached models. */
    public function testDetachedModelCannotRunRelativeXpath()
    {
        $node = new DomNodeModel('item', 'value');

        $this->expectException(LogicException::class);
        $node->query('./title');
    }

    /** @testdox DomNodeModel rejects invalid child types. */
    public function testNodeRejectsInvalidChildType()
    {
        $this->expectException(InvalidArgumentException::class);
        new DomNodeModel('root', null, [], ['invalid-child']);
    }

    /** @testdox DomNodeModel rejects invalid native DOM source values. */
    public function testNodeRejectsInvalidDomSourceType()
    {
        $this->expectException(InvalidArgumentException::class);
        new DomNodeModel('root', null, [], [], 'not-a-dom-node');
    }

    /** @testdox DomNodeCollection rejects non-model members. */
    public function testCollectionRejectsInvalidMemberType()
    {
        $this->expectException(InvalidArgumentException::class);
        new DomNodeCollection([new DomNodeModel('ok'), 'invalid']);
    }

    /** @testdox DomNodeModel array access is immutable. */
    public function testNodeOffsetSetIsImmutable()
    {
        $node = new DomNodeModel('root');

        $this->expectException(LogicException::class);
        $node['child'] = new DomNodeModel('child');
    }

    /** @testdox DomNodeModel array unsetting is immutable. */
    public function testNodeOffsetUnsetIsImmutable()
    {
        $node = new DomNodeModel('root', null, [], [new DomNodeModel('child')]);

        $this->expectException(LogicException::class);
        unset($node['child']);
    }

    /** @testdox DomNodeCollection array access is immutable. */
    public function testCollectionOffsetSetIsImmutable()
    {
        $collection = new DomNodeCollection([new DomNodeModel('item')]);

        $this->expectException(LogicException::class);
        $collection[0] = new DomNodeModel('replacement');
    }

    /** @testdox DomNodeCollection array unsetting is immutable. */
    public function testCollectionOffsetUnsetIsImmutable()
    {
        $collection = new DomNodeCollection([new DomNodeModel('item')]);

        $this->expectException(LogicException::class);
        unset($collection[0]);
    }

    /** @testdox Parsing restores a disabled libxml internal error mode. */
    public function testLibxmlDisabledErrorModeIsRestored()
    {
        $original = libxml_use_internal_errors(false);

        try {
            GenericParser::getDocumentModel('<root><item>ok</item></root>', 'xml');
            static::assertFalse(libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($original);
            libxml_clear_errors();
        }
    }

    /** @testdox Parsing restores an enabled libxml internal error mode. */
    public function testLibxmlEnabledErrorModeIsRestored()
    {
        $original = libxml_use_internal_errors(true);

        try {
            GenericParser::getDocumentModel('<root><item>ok</item></root>', 'xml');
            static::assertTrue(libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($original);
            libxml_clear_errors();
        }
    }

    /** @testdox Failed XML parsing also restores libxml internal error mode. */
    public function testFailedParseRestoresLibxmlErrorMode()
    {
        $original = libxml_use_internal_errors(false);

        try {
            try {
                GenericParser::getDocumentModel('<root><broken></root>', 'xml');
                static::fail('Invalid XML should have thrown RuntimeException.');
            } catch (RuntimeException $e) {
                static::assertFalse(libxml_use_internal_errors());
            }
        } finally {
            libxml_use_internal_errors($original);
            libxml_clear_errors();
        }
    }
}
