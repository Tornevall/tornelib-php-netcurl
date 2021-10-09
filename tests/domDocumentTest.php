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
     * @since 6.1.5
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
            $href = GenericParser::getValuesFromXPath(
                $node,
                ['subtitle', 'mainNode', 'href'],
                ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']
            );
            $hrefText = GenericParser::getValuesFromXPath(
                $node,
                ['subtitle', 'subNode', 'value'],
                ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']
            );
            $description = GenericParser::getValuesFromXPath(
                $node,
                ['lead', 'subNode', 'value'],
                ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']
            );
            if (!empty($href)) {
                $articles[$href] = [
                    'title' => $hrefText,
                    'description' => $description,
                ];
            }
        }
        static::assertCount(20, $articles);
    }

    /**
     * @test
     * @testdox As the basic xPathTest but in one shot.
     * @since 6.1.5
     */
    function genericXpathCompiled()
    {
        $nodeList = GenericParser::getContentFromXPath
        (
            file_get_contents(__DIR__ . '/templates/domdocument_mz.html'),
            [
                '//*[@class="inner_article"]/a',
                '//*[@class="articles_wrapper"]/a',
            ],
            [
                'subtitle' => '/*[contains(@class, "subtitle")]',
                'lead' => '/*[contains(@class, "lead")]',
            ],
            ['href', 'value'],
            ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']
        );

        static::assertCount(20, $nodeList['rendered']);
    }
}
