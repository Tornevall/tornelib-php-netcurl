<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\DomNodeModel;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class structuredDomRssTest extends TestCase
{
    /** @testdox RSS 2.0 items can be traversed without the legacy rendered array format. */
    public function testRss20ItemTraversal()
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Demo feed</title>
        <item>
            <title>First post</title>
            <link>https://example.test/first</link>
            <description><![CDATA[<p>First description</p>]]></description>
            <pubDate>Thu, 20 Aug 2026 10:00:00 +0200</pubDate>
            <guid isPermaLink="false">post-1</guid>
        </item>
        <item>
            <title>Second post</title>
            <link>https://example.test/second</link>
            <description>Second description</description>
            <pubDate>Thu, 20 Aug 2026 11:00:00 +0200</pubDate>
            <guid isPermaLink="false">post-2</guid>
        </item>
    </channel>
</rss>
XML;

        $document = GenericParser::getDocumentModel($xml, 'xml');
        $items = $document->query('/rss/channel/item');

        static::assertCount(2, $items);
        static::assertSame('Demo feed', $document->getRoot()->channel->title->getValue());
        static::assertSame('First post', $items[0]->queryFirst('./title')->getValue());
        static::assertSame('https://example.test/first', $items[0]->queryFirst('./link')->getValue());
        static::assertSame('<p>First description</p>', $items[0]->queryFirst('./description')->getValue());
        static::assertSame('Thu, 20 Aug 2026 10:00:00 +0200', $items[0]->queryFirst('./pubDate')->getValue());
        static::assertSame('post-1', $items[0]->queryFirst('./guid')->getValue());
        static::assertSame('false', $items[0]->queryFirst('./guid')->getAttribute('isPermaLink'));
    }

    /** @testdox Repeated RSS categories remain an ordered collection. */
    public function testRssRepeatedCategories()
    {
        $xml = '<rss><channel><item><category>News</category><category>Sweden</category></item></channel></rss>';
        $item = GenericParser::getModelsFromXPath($xml, '/rss/channel/item', 'xml')->first();
        $categories = $item->children('category');

        static::assertCount(2, $categories);
        static::assertSame('News', $categories[0]->getValue());
        static::assertSame('Sweden', $categories[1]->getValue());
        static::assertSame(['category' => ['News', 'Sweden']], $item->toArray());
    }

    /** @testdox Atom default namespaces and href attributes can be queried using structured models. */
    public function testAtomFeedTraversal()
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Atom demo</title>
    <entry>
        <title>Atom first</title>
        <link rel="alternate" href="https://example.test/atom-first" />
        <updated>2026-08-20T10:00:00+02:00</updated>
        <id>tag:example.test,2026:first</id>
    </entry>
</feed>
XML;

        $document = GenericParser::getDocumentModel($xml, 'xml');
        $entry = $document->query('//default:entry')->first();

        static::assertInstanceOf(DomNodeModel::class, $entry);
        static::assertSame('Atom first', $entry->queryFirst('./default:title')->getValue());
        static::assertSame(
            'https://example.test/atom-first',
            $entry->queryFirst('./default:link[@rel="alternate"]/@href')->getValue()
        );
        static::assertSame('2026-08-20T10:00:00+02:00', $entry->queryFirst('./default:updated')->getValue());
    }

    /** @testdox RSS namespace extensions remain available through prefixed XPath. */
    public function testRssNamespaceExtensions()
    {
        $xml = <<<'XML'
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <item>
            <title>Extended post</title>
            <dc:creator>Thomas</dc:creator>
            <content:encoded><![CDATA[<article>Full content</article>]]></content:encoded>
        </item>
    </channel>
</rss>
XML;

        $document = GenericParser::getDocumentModel($xml, 'xml');
        $item = $document->query('/rss/channel/item')->first();

        static::assertSame('Thomas', $item->queryFirst('./dc:creator')->getValue());
        static::assertSame('<article>Full content</article>', $item->queryFirst('./content:encoded')->getValue());
    }

    /** @testdox Multiple prefixed RSS namespaces stay available together. */
    public function testMultipleRssNamespaceExtensions()
    {
        $xml = <<<'XML'
<rss xmlns:media="http://search.yahoo.com/mrss/" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
    <channel>
        <item>
            <media:content url="https://example.test/audio.mp3" type="audio/mpeg" />
            <itunes:duration>01:02:03</itunes:duration>
        </item>
    </channel>
</rss>
XML;

        $item = GenericParser::getDocumentModel($xml, 'xml')->query('/rss/channel/item')->first();

        static::assertSame('https://example.test/audio.mp3', $item->queryFirst('./media:content/@url')->getValue());
        static::assertSame('audio/mpeg', $item->queryFirst('./media:content/@type')->getValue());
        static::assertSame('01:02:03', $item->queryFirst('./itunes:duration')->getValue());
    }

    /** @testdox Sitemap default and XHTML namespaces can be traversed without local-name workarounds. */
    public function testSitemapNamespaceTraversal()
    {
        $xml = <<<'XML'
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url>
        <loc>https://example.test/en/movie/abc</loc>
        <lastmod>2026-08-19</lastmod>
        <xhtml:link rel="alternate" hreflang="sv-se" href="https://example.test/sv/movie/abc" />
        <xhtml:link rel="alternate" hreflang="en-gb" href="https://example.test/en-gb/movie/abc" />
    </url>
</urlset>
XML;

        $document = GenericParser::getDocumentModel($xml, 'xml');
        $url = $document->query('//default:url')->first();
        $alternates = $url->query('./xhtml:link[@rel="alternate"]');

        static::assertSame('https://example.test/en/movie/abc', $url->queryFirst('./default:loc')->getValue());
        static::assertSame('2026-08-19', $url->queryFirst('./default:lastmod')->getValue());
        static::assertCount(2, $alternates);
        static::assertSame('sv-se', $alternates[0]->getAttribute('hreflang'));
        static::assertSame('https://example.test/en-gb/movie/abc', $alternates[1]->getAttribute('href'));
    }

    /** @testdox HTML article extraction can use the same row and relative XPath model as XML feeds. */
    public function testHtmlArticleExtractionModel()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <section class="results">
        <article data-id="a1">
            <h2><a href="/news/one">One</a></h2>
            <p class="lead">First lead</p>
            <time datetime="2026-08-20T08:00:00+02:00">Today</time>
        </article>
        <article data-id="a2">
            <h2><a href="/news/two">Two</a></h2>
            <p class="lead">Second lead</p>
            <time datetime="2026-08-20T09:00:00+02:00">Today</time>
        </article>
    </section>
</body>
</html>
HTML;

        $document = GenericParser::getDocumentModel($html, 'html');
        $articles = $document->query('//article');

        static::assertCount(2, $articles);
        static::assertSame('One', $articles[0]->queryFirst('.//h2/a')->getValue());
        static::assertSame('/news/one', $articles[0]->queryFirst('.//h2/a/@href')->getValue());
        static::assertSame('First lead', $articles[0]->queryFirst('.//p[@class="lead"]')->getValue());
        static::assertSame('2026-08-20T08:00:00+02:00', $articles[0]->queryFirst('.//time/@datetime')->getValue());
    }

    /** @testdox XML without a declaration can still be consumed as an RSS-like structure in auto mode. */
    public function testRssLikeXmlAutoMode()
    {
        $xml = '<rss><channel><item><title>Auto RSS</title></item></channel></rss>';
        $document = GenericParser::getDocumentModel($xml);

        static::assertSame('xml', $document->getFormat());
        static::assertSame('Auto RSS', $document->query('/rss/channel/item/title')->first()->getValue());
    }
}
