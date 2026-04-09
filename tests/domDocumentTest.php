<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class domDocumentTest extends TestCase
{
    /**
     * @testdox Test DOMDocument wrapper.
     * @throws Exception
     * @since 6.1.5
     */
    public function testXPathTest()
    {
        $elements = ['subtitle' => '/*[contains(@class, "subtitle")]', 'lead' => '/*[contains(@class, "lead")]',];

        $xData = GenericParser::getFromXPath(file_get_contents(__DIR__ . '/templates/domdocument_mz.html'), ['//*[@class="inner_article"]/a', '//*[@class="articles_wrapper"]/a',]);

        $nodeInfo = GenericParser::getElementsByXPath($xData, $elements, ['href', 'value']);
        $articles = [];
        foreach ($nodeInfo as $node) {
            $href = GenericParser::getValuesFromXPath($node, ['subtitle', 'mainNode', 'href'], ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']);
            $hrefText = GenericParser::getValuesFromXPath($node, ['subtitle', 'subNode', 'value'], ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']);
            $description = GenericParser::getValuesFromXPath($node, ['lead', 'subNode', 'value'], ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']);
            if (!empty($href)) {
                $articles[$href] = ['title' => $hrefText, 'description' => $description,];
            }
        }
        static::assertCount(20, $articles);
    }

    /**
     * @testdox As the basic xPathTest but in one shot.
     * @since 6.1.5
     */
    public function testGenericXpathCompiled()
    {
        $nodeList = GenericParser::getContentFromXPath(file_get_contents(__DIR__ . '/templates/domdocument_mz.html'), ['//*[@class="inner_article"]/a', '//*[@class="articles_wrapper"]/a',], ['subtitle' => '/*[contains(@class, "subtitle")]', 'lead' => '/*[contains(@class, "lead")]',], ['href', 'value'], ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']);

        static::assertCount(20, $nodeList['rendered']);
    }

    /**
     * @testdox Null DOM nodes in partially matching XPath trees should not crash on PHP 8+
     * @since 6.1.10
     */
    public function testGenericXpathCompiledWithMissingOptionalNodeDoesNotCrash()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
  <body>
    <div class="articles_wrapper">
      <a href="https://example.test/one">
        <span class="subtitle">Headline</span>
      </a>
    </div>
  </body>
</html>
HTML;

        $nodeList = GenericParser::getContentFromXPath(
            $html,
            ['//*[@class="articles_wrapper"]/a'],
            [
                'subtitle' => '/*[contains(@class, "subtitle")]',
                'lead' => '/*[contains(@class, "lead")]',
            ],
            ['href', 'value'],
            ['subtitle' => 'mainNode', 'lead' => 'subNode', 'href' => 'mainNode']
        );

        static::assertCount(1, $nodeList['rendered']);
        static::assertSame('https://example.test/one', $nodeList['rendered'][0]['subtitle']['href']);
        static::assertSame('Headline', $nodeList['rendered'][0]['subtitle']['value']);
        static::assertNull($nodeList['rendered'][0]['lead']['value']);
    }
}
