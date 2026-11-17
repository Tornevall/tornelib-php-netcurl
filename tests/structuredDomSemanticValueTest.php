<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\DomSemanticSearch;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class structuredDomSemanticValueTest extends TestCase
{
    public function test_direct_attribute_xpath_can_be_read_as_text_value()
    {
        $row = GenericParser::getModelsFromXPath(
            '<root><item><a href="/one">One</a></item></root>',
            '/root/item',
            'xml'
        )->first();

        $href = $row->queryFirst('./a/@href');

        static::assertSame('/one', $href->getValue());
        static::assertSame('/one', DomSemanticSearch::getTextContent($href));
    }

    public function test_nested_element_text_is_normalized_in_source_order()
    {
        $row = GenericParser::getModelsFromXPath(
            '<root><item><h2>Hello <span>nested</span> world</h2></item></root>',
            '/root/item',
            'xml'
        )->first();

        static::assertSame('Hello nested world', DomSemanticSearch::getTextContent($row->queryFirst('./h2')));
    }
}
