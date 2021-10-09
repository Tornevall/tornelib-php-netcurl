<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class domDocumentTest extends TestCase
{
    /**
     * @test
     * @testdox Test DOMDocument wrapper.
     * @throws Exception
     */
    function xPathTest()
    {
        $elements = [
            'subtitle' => '/*[contains(@class, "subtitle")]',
            'lead' => '/*[contains(@class, "lead")]',
        ];

        $xData = GenericParser::getFromXPath(
            file_get_contents(__DIR__ . '/templates/domdocument_mz.html'),
            [
                '//*[@class="inner_article"]/a',
                '//*[@class="articles_wrapper"]/a',
            ]
        );

        $nodeInfo = GenericParser::getElementsByXPath($xData, $elements, ['href', 'value']);
        $articles = [];
        foreach ($nodeInfo as $node) {
            $href = GenericParser::getValuesFromXPath($node, ['subtitle', 'mainNode', 'href']);
            $hrefText = GenericParser::getValuesFromXPath($node, ['subtitle', 'subNode', 'value']);
            $description = GenericParser::getValuesFromXPath($node, ['lead', 'subNode', 'value']);
            if (!empty($href)) {
                $articles[$href] = [
                    'title' => $hrefText,
                    'description' => $description,
                ];
            }
        }
        static::assertCount(20, $articles);
    }
}
