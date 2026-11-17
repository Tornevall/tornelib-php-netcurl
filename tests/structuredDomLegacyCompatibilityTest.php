<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\DomDocumentModel;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class structuredDomLegacyCompatibilityTest extends TestCase
{
    /** @testdox GenericParser still routes legacy getContentFromXPath calls to SimpleDomParser. */
    public function testLegacyCompiledXpathStillReturnsRenderedStructure()
    {
        $result = GenericParser::getContentFromXPath(
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
            [
                'subtitle' => 'mainNode',
                'lead' => 'subNode',
                'href' => 'mainNode',
            ]
        );

        static::assertIsArray($result);
        static::assertArrayHasKey('rendered', $result);
        static::assertCount(20, $result['rendered']);
        static::assertIsArray($result['rendered'][0]);
        static::assertArrayHasKey('subtitle', $result['rendered'][0]);
        static::assertArrayHasKey('lead', $result['rendered'][0]);
    }

    /** @testdox New structured parser methods are additive and do not alter legacy method names. */
    public function testLegacyAndStructuredMethodsCoexistThroughGenericParser()
    {
        $html = '<html><body><div class="row"><a href="/one"><span class="subtitle">One</span></a></div></body></html>';

        $legacy = GenericParser::getContentFromXPath(
            $html,
            ['//*[@class="row"]/a'],
            ['subtitle' => '/*[contains(@class, "subtitle")]'],
            ['href', 'value'],
            ['subtitle' => 'mainNode', 'href' => 'mainNode']
        );
        $structured = GenericParser::getDocumentModel($html, 'html');

        static::assertArrayHasKey('rendered', $legacy);
        static::assertCount(1, $legacy['rendered']);
        static::assertInstanceOf(DomDocumentModel::class, $structured);
        static::assertSame('One', $structured->query('//span')->first()->getValue());
    }

    /** @testdox Missing optional legacy XPath child nodes remain null instead of throwing. */
    public function testLegacyMissingOptionalNodeRemainsNull()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <div class="articles_wrapper">
        <a href="https://example.test/one">
            <span class="subtitle">Headline</span>
        </a>
    </div>
</body>
</html>
HTML;

        $result = GenericParser::getContentFromXPath(
            $html,
            ['//*[@class="articles_wrapper"]/a'],
            [
                'subtitle' => '/*[contains(@class, "subtitle")]',
                'lead' => '/*[contains(@class, "lead")]',
            ],
            ['href', 'value'],
            [
                'subtitle' => 'mainNode',
                'lead' => 'subNode',
                'href' => 'mainNode',
            ]
        );

        static::assertCount(1, $result['rendered']);
        static::assertSame('Headline', $result['rendered'][0]['subtitle']['value']);
        static::assertNull($result['rendered'][0]['lead']['value']);
    }

    /** @testdox Structured parsing does not modify a subsequent legacy parser result. */
    public function testStructuredCallDoesNotLeakIntoLegacyParserState()
    {
        $html = '<html><body><a class="entry" href="/one"><span class="subtitle">One</span></a></body></html>';

        GenericParser::getDocumentModel('<root><item>xml</item></root>', 'xml');

        $legacy = GenericParser::getContentFromXPath(
            $html,
            ['//*[@class="entry"]'],
            ['subtitle' => '/*[contains(@class, "subtitle")]'],
            ['href', 'value'],
            ['subtitle' => 'mainNode', 'href' => 'mainNode']
        );

        static::assertCount(1, $legacy['rendered']);
        static::assertSame('/one', $legacy['rendered'][0]['subtitle']['href']);
        static::assertSame('One', $legacy['rendered'][0]['subtitle']['value']);
    }
}
