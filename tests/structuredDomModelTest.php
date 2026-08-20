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
}
