<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\DomDocumentModel;
use TorneLIB\Helpers\DomNodeCollection;
use TorneLIB\Helpers\DomNodeModel;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class structuredDomModelTest extends TestCase
{
    /**
     * @testdox XML can be parsed into traversable models and JSON-friendly arrays.
     * @since 6.1.11
     */
    public function testStructuredXmlModel()
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<catalog source="demo">
    <item id="one">
        <title>First item</title>
        <price currency="SEK">100</price>
    </item>
    <item id="two">
        <title>Second item</title>
        <price currency="SEK">200</price>
    </item>
</catalog>
XML;

        $document = GenericParser::getDocumentModel($xml, 'xml');

        static::assertInstanceOf(DomDocumentModel::class, $document);
        static::assertSame('xml', $document->getFormat());
        static::assertSame('catalog', $document->getRoot()->getName());
        static::assertSame('demo', $document->getRoot()->getAttribute('source'));

        $items = $document->getRoot()->children('item');
        static::assertInstanceOf(DomNodeCollection::class, $items);
        static::assertCount(2, $items);
        static::assertSame('one', $items[0]->getAttribute('id'));
        static::assertSame('First item', $items[0]->get('title')->getValue());
        static::assertSame('Second item', $items[1]->title->getValue());

        static::assertSame(
            [
                'catalog' => [
                    '_attributes' => ['source' => 'demo'],
                    'item' => [
                        [
                            '_attributes' => ['id' => 'one'],
                            'title' => 'First item',
                            'price' => [
                                '_attributes' => ['currency' => 'SEK'],
                                '_value' => '100',
                            ],
                        ],
                        [
                            '_attributes' => ['id' => 'two'],
                            'title' => 'Second item',
                            'price' => [
                                '_attributes' => ['currency' => 'SEK'],
                                '_value' => '200',
                            ],
                        ],
                    ],
                ],
            ],
            $document->toArray()
        );

        static::assertSame(json_encode($document->toArray()), json_encode($document));
    }

    /**
     * @testdox XPath matches are returned as a traversable model collection.
     * @since 6.1.11
     */
    public function testStructuredXPathCollection()
    {
        $xml = '<root><entry key="a">Alpha</entry><entry key="b">Beta</entry></root>';

        $entries = GenericParser::getModelsFromXPath($xml, '/root/entry', 'xml');

        static::assertInstanceOf(DomNodeCollection::class, $entries);
        static::assertCount(2, $entries);
        static::assertInstanceOf(DomNodeModel::class, $entries->first());
        static::assertSame('a', $entries->first()->getAttribute('key'));
        static::assertSame('Alpha', $entries->first()->getValue());

        $values = [];
        foreach ($entries as $entry) {
            $values[] = $entry->getValue();
        }

        static::assertSame(['Alpha', 'Beta'], $values);
    }

    /**
     * @testdox XPath matches can be queried relative to each matched model.
     * @since 6.1.11
     */
    public function testStructuredRelativeXPathOnMatchedNode()
    {
        $xml = <<<'XML'
<feed>
    <item id="one">
        <title>First item</title>
        <link href="https://example.test/one" />
    </item>
    <item id="two">
        <title>Second item</title>
        <link href="https://example.test/two" />
    </item>
</feed>
XML;

        $items = GenericParser::getModelsFromXPath($xml, '/feed/item', 'xml');

        static::assertCount(2, $items);
        static::assertSame('First item', $items[0]->queryFirst('./title')->getValue());
        static::assertSame('https://example.test/one', $items[0]->queryFirst('./link')->getAttribute('href'));
        static::assertSame('https://example.test/two', $items[1]->queryFirst('./link/@href')->getValue());
    }

    /**
     * @testdox HTML uses the forgiving HTML parser and remains XPath-queryable.
     * @since 6.1.11
     */
    public function testStructuredHtmlModel()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
    <body>
        <article data-id="1"><h2>First</h2></article>
        <article data-id="2"><h2>Second</h2></article>
    </body>
</html>
HTML;

        $document = GenericParser::getDocumentModel($html);
        $articles = $document->query('//article');

        static::assertSame('html', $document->getFormat());
        static::assertCount(2, $articles);
        static::assertSame('1', $articles[0]->getAttribute('data-id'));
        static::assertSame('First', $articles[0]->h2->getValue());
        static::assertSame('First', $articles[0]->queryFirst('./h2')->getValue());
    }

    /**
     * @testdox Malformed HTML is accepted when HTML mode is explicitly selected.
     * @since 6.1.11
     */
    public function testStructuredMalformedHtmlModel()
    {
        $html = '<html><body><ul><li>One<li>Two</ul></body></html>';
        $document = GenericParser::getDocumentModel($html, 'html');
        $items = $document->query('//li');

        static::assertCount(2, $items);
        static::assertSame('One', $items[0]->getValue());
        static::assertSame('Two', $items[1]->getValue());
    }

    /**
     * @testdox Well-formed HTML fragments can be forced to HTML instead of XML auto detection.
     * @since 6.1.11
     */
    public function testStructuredExplicitHtmlForAmbiguousFragment()
    {
        $fragment = '<div class="card"><span>Fragment</span></div>';
        $auto = GenericParser::getDocumentModel($fragment);
        $html = GenericParser::getDocumentModel($fragment, 'html');

        static::assertSame('xml', $auto->getFormat());
        static::assertSame('html', $html->getFormat());
        static::assertSame('Fragment', $html->query('//span')->first()->getValue());
    }

    /**
     * @testdox Explicit namespaces can be used for XML XPath queries.
     * @since 6.1.11
     */
    public function testStructuredXmlNamespaces()
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="urn:example:feed">
    <entry><title>Namespaced</title></entry>
</feed>
XML;

        $document = GenericParser::getDocumentModel($xml, 'xml');
        $entries = $document->query('//feed:entry', ['feed' => 'urn:example:feed']);

        static::assertCount(1, $entries);
        static::assertSame('Namespaced', $entries[0]->get('title')->getValue());
        static::assertSame(
            'Namespaced',
            $entries[0]->queryFirst('./feed:title', ['feed' => 'urn:example:feed'])->getValue()
        );
    }

    /**
     * @testdox Default XML namespaces are exposed through the default synthetic prefix.
     * @since 6.1.11
     */
    public function testStructuredDefaultNamespaceDiscovery()
    {
        $xml = '<feed xmlns="urn:example:feed"><entry><title>Automatic</title></entry></feed>';
        $document = GenericParser::getDocumentModel($xml, 'xml');

        static::assertSame(['default' => 'urn:example:feed'], $document->getNamespaces());

        $entry = $document->query('//default:entry')->first();
        static::assertInstanceOf(DomNodeModel::class, $entry);
        static::assertSame('Automatic', $entry->queryFirst('./default:title')->getValue());
    }

    /**
     * @testdox Prefixed XML namespaces can be discovered and queried.
     * @since 6.1.11
     */
    public function testStructuredPrefixedNamespaceDiscovery()
    {
        $xml = <<<'XML'
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url>
        <loc>https://example.test/one</loc>
        <xhtml:link rel="alternate" hreflang="sv" href="https://example.test/sv/one" />
    </url>
</urlset>
XML;
        $document = GenericParser::getDocumentModel($xml, 'xml');
        $namespaces = $document->getNamespaces();

        static::assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $namespaces['default']);
        static::assertSame('http://www.w3.org/1999/xhtml', $namespaces['xhtml']);

        $url = $document->query('//default:url')->first();
        static::assertSame('https://example.test/one', $url->queryFirst('./default:loc')->getValue());
        static::assertSame(
            'https://example.test/sv/one',
            $url->queryFirst('./xhtml:link/@href')->getValue()
        );
    }

    /**
     * @testdox Auto mode recognizes well-formed XML without an XML declaration.
     * @since 6.1.11
     */
    public function testStructuredAutoDetectsXmlWithoutDeclaration()
    {
        $document = GenericParser::getDocumentModel('<root><child>value</child></root>');

        static::assertSame('xml', $document->getFormat());
        static::assertSame('value', $document->getRoot()->child->getValue());
    }

    /**
     * @testdox Auto mode recognizes explicit HTML documents as HTML.
     * @since 6.1.11
     */
    public function testStructuredAutoDetectsHtmlDocument()
    {
        $document = GenericParser::getDocumentModel('<!DOCTYPE html><html><body><p>Hello</p></body></html>');

        static::assertSame('html', $document->getFormat());
        static::assertSame('Hello', $document->query('//p')->first()->getValue());
    }

    /**
     * @testdox Repeated child names become ordered arrays while single children remain scalar values.
     * @since 6.1.11
     */
    public function testStructuredRepeatedChildrenSerialization()
    {
        $document = GenericParser::getDocumentModel(
            '<root><tag>one</tag><tag>two</tag><single>three</single></root>',
            'xml'
        );

        static::assertSame(
            [
                'root' => [
                    'tag' => ['one', 'two'],
                    'single' => 'three',
                ],
            ],
            $document->toArray()
        );
    }

    /**
     * @testdox Attributes and direct text are preserved together in JSON-friendly output.
     * @since 6.1.11
     */
    public function testStructuredAttributeAndValueSerialization()
    {
        $document = GenericParser::getDocumentModel('<root><price currency="SEK">199</price></root>', 'xml');

        static::assertSame(
            [
                'root' => [
                    'price' => [
                        '_attributes' => ['currency' => 'SEK'],
                        '_value' => '199',
                    ],
                ],
            ],
            $document->toArray()
        );
    }

    /**
     * @testdox CDATA content is exposed as the node value without parsing embedded markup as children.
     * @since 6.1.11
     */
    public function testStructuredCdataValue()
    {
        $xml = '<root><description><![CDATA[<p>Hello & goodbye</p>]]></description></root>';
        $description = GenericParser::getDocumentModel($xml, 'xml')->getRoot()->description;

        static::assertSame('<p>Hello & goodbye</p>', $description->getValue());
        static::assertCount(0, $description);
    }

    /**
     * @testdox XML entities are decoded by DOM before values are exposed.
     * @since 6.1.11
     */
    public function testStructuredXmlEntityValue()
    {
        $xml = '<root><title>Tom &amp; Jerry &lt;3</title></root>';
        $title = GenericParser::getDocumentModel($xml, 'xml')->getRoot()->title;

        static::assertSame('Tom & Jerry <3', $title->getValue());
    }

    /**
     * @testdox Empty elements remain present and serialize to null.
     * @since 6.1.11
     */
    public function testStructuredEmptyElement()
    {
        $root = GenericParser::getDocumentModel('<root><empty/><blank>   </blank></root>', 'xml')->getRoot();

        static::assertTrue(isset($root->empty));
        static::assertNull($root->empty->getValue());
        static::assertNull($root->blank->getValue());
        static::assertSame(['empty' => null, 'blank' => null], $root->toArray());
    }

    /**
     * @testdox Node models support property, array, iterator and count traversal consistently.
     * @since 6.1.11
     */
    public function testStructuredNodeTraversalInterfaces()
    {
        $root = GenericParser::getDocumentModel('<root><one>1</one><two>2</two></root>', 'xml')->getRoot();

        static::assertCount(2, $root);
        static::assertTrue(isset($root['one']));
        static::assertFalse(isset($root['missing']));
        static::assertSame('1', $root['one']->getValue());
        static::assertSame('2', $root->two->getValue());

        $names = [];
        foreach ($root as $child) {
            $names[] = $child->getName();
        }

        static::assertSame(['one', 'two'], $names);
    }

    /**
     * @testdox Node collections support count, first, all, array access and iteration.
     * @since 6.1.11
     */
    public function testStructuredCollectionTraversalInterfaces()
    {
        $items = GenericParser::getModelsFromXPath('<root><item>A</item><item>B</item></root>', '//item', 'xml');

        static::assertCount(2, $items);
        static::assertSame('A', $items->first()->getValue());
        static::assertCount(2, $items->all());
        static::assertTrue(isset($items[1]));
        static::assertFalse(isset($items[2]));
        static::assertNull($items[2]);
        static::assertSame(['A', 'B'], $items->toArray());
        static::assertSame(json_encode(['A', 'B']), json_encode($items));
    }

    /**
     * @testdox A document iterator exposes direct children of the root node in source order.
     * @since 6.1.11
     */
    public function testStructuredDocumentIterator()
    {
        $document = GenericParser::getDocumentModel('<root><first/><second/></root>', 'xml');
        $names = [];

        foreach ($document as $child) {
            $names[] = $child->getName();
        }

        static::assertSame(['first', 'second'], $names);
    }

    /**
     * @testdox XPath queries with no matches return an empty collection rather than null.
     * @since 6.1.11
     */
    public function testStructuredNoXPathMatchesReturnsEmptyCollection()
    {
        $document = GenericParser::getDocumentModel('<root><item>one</item></root>', 'xml');
        $matches = $document->query('//missing');

        static::assertInstanceOf(DomNodeCollection::class, $matches);
        static::assertCount(0, $matches);
        static::assertNull($matches->first());
    }

    /**
     * @testdox Attribute XPath matches are represented as node models with their attribute value.
     * @since 6.1.11
     */
    public function testStructuredAttributeXPathResult()
    {
        $document = GenericParser::getDocumentModel('<root><link href="https://example.test/"/></root>', 'xml');
        $attribute = $document->query('//link/@href')->first();

        static::assertInstanceOf(DomNodeModel::class, $attribute);
        static::assertSame('href', $attribute->getName());
        static::assertSame('https://example.test/', $attribute->getValue());
    }
}
