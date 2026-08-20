<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\DomNodeModel;
use TorneLIB\Helpers\DomSemanticSearch;
use TorneLIB\Helpers\GenericParser;

require_once(sprintf("%s/../vendor/autoload.php", __DIR__));

class structuredDomDiscoveryTest extends TestCase
{
    /** @testdox Semantic search maps common structural names to useful elements. */
    public function testSemanticSearchFindsCommonFieldConcepts()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<article class="news-card">
    <h2 class="headline">Headline text</h2>
    <p class="summary">Summary text</p>
    <a class="permalink" href="/one">Read</a>
    <time datetime="2026-08-20">20 Aug</time>
</article>
</body></html>
HTML;

        $document = GenericParser::getDocumentModel($html, 'html');

        static::assertSame('h2', DomSemanticSearch::findBySemanticNames($document, ['title'])->first()->getName());
        static::assertSame('p', DomSemanticSearch::findBySemanticNames($document, ['description'])->first()->getName());
        static::assertSame('a', DomSemanticSearch::findBySemanticNames($document, ['link'])->first()->getName());
        static::assertSame('time', DomSemanticSearch::findBySemanticNames($document, ['date'])->first()->getName());
    }

    /** @testdox Inventory exposes tag and semantic attribute names for exploration UIs. */
    public function testElementInventory()
    {
        $html = '<main><article class="news-card featured" itemprop="articleBody"><h2 class="headline">One</h2></article><article class="news-card"><h2 class="headline">Two</h2></article></main>';
        $inventory = GenericParser::getDomElementInventory($html, 'html');

        static::assertSame(2, $inventory['tags']['article']);
        static::assertSame(2, $inventory['classes']['news-card']);
        static::assertSame(2, $inventory['classes']['headline']);
        static::assertSame(1, $inventory['itemprops']['articlebody']);
        static::assertGreaterThanOrEqual(2, $inventory['semantic_names']['title']);
    }

    /** @testdox Repeated card structures produce reusable XPath candidates. */
    public function testRepeatedStructureDiscovery()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<div class="results">
    <article class="news-card"><h2>One</h2><a href="/one">One</a><p>Lead one</p></article>
    <article class="news-card"><h2>Two</h2><a href="/two">Two</a><p>Lead two</p></article>
    <article class="news-card"><h2>Three</h2><a href="/three">Three</a><p>Lead three</p></article>
</div>
</body></html>
HTML;

        $candidates = GenericParser::discoverDomStructures($html, 'html', 2, 10);
        $candidate = $this->findCandidateByClass($candidates, 'news-card');

        static::assertNotNull($candidate);
        static::assertSame(3, $candidate['count']);
        static::assertStringContainsString('news-card', $candidate['xpath']);
        static::assertGreaterThan(0, $candidate['score']);

        $document = GenericParser::getDocumentModel($html, 'html');
        static::assertCount(3, $document->query($candidate['xpath']));
    }

    /** @testdox Existing real MovieZine snapshot is discoverable without legacy XPath rules. */
    public function testMovieZineSnapshotDiscovery()
    {
        $html = file_get_contents(__DIR__ . '/templates/domdocument_mz.html');
        $document = GenericParser::getDocumentModel($html, 'html');
        $candidates = DomSemanticSearch::discoverRepeatedStructures($document, 2, 50);
        $candidate = $this->findCandidateByClass($candidates, 'inner_article');

        static::assertNotNull($candidate, 'Expected discovery to find MovieZine a.inner_article rows.');
        static::assertSame(20, $candidate['count']);

        $rows = $document->query($candidate['xpath']);
        static::assertCount(20, $rows);

        /** @var DomNodeModel $first */
        $first = $rows->first();
        $titles = DomSemanticSearch::findBySemanticNames($first, ['title', 'headline']);
        $descriptions = DomSemanticSearch::findBySemanticNames($first, ['description', 'lead', 'summary']);
        $dates = DomSemanticSearch::findBySemanticNames($first, ['date', 'published', 'time']);

        static::assertNotNull($titles->first());
        static::assertSame('h3', $titles->first()->getName());
        static::assertNotSame('', DomSemanticSearch::getTextContent($titles->first()));
        static::assertNotNull($descriptions->first());
        static::assertNotNull($dates->first());
        static::assertNotEmpty($first->getAttribute('href'));
    }

    /** @testdox Socialdemokraterna Sitevision snapshot exposes row, heading, link and description candidates. */
    public function testSocialdemokraternaSitevisionSnapshotDiscovery()
    {
        $html = file_get_contents(__DIR__ . '/templates/sitevision_socialdemokraterna_snapshot.html');
        $document = GenericParser::getDocumentModel($html, 'html');
        $candidates = DomSemanticSearch::discoverRepeatedStructures($document, 2, 25);
        $candidate = $this->findCandidateByClass($candidates, 'sap-search__result-item');

        static::assertNotNull($candidate);
        static::assertSame(2, $candidate['count']);

        $rows = $document->query($candidate['xpath']);
        static::assertCount(2, $rows);

        $first = $rows->first();
        $headings = DomSemanticSearch::findBySemanticNames($first, ['title', 'headline', 'heading']);
        $links = DomSemanticSearch::findBySemanticNames($first, ['link', 'url']);
        $descriptions = DomSemanticSearch::findBySemanticNames($first, ['description', 'summary']);
        $metadata = DomSemanticSearch::findBySemanticNames($first, ['meta']);

        static::assertNotNull($headings->first());
        static::assertSame('a', $headings->first()->getName());
        static::assertStringContainsString('Socialdemokraterna går till val', DomSemanticSearch::getTextContent($headings->first()));
        static::assertSame('/nyheter/2026/2026-08-05-socialdemokraterna-gar-till-val-pa-att-stoppa-sloseriet-med-skattepengar', $links->first()->getAttribute('href'));
        static::assertStringContainsString('Mikael Damberg', DomSemanticSearch::getTextContent($descriptions->first()));
        static::assertNotNull($metadata->first());
        static::assertStringContainsString('05 augusti 2026', DomSemanticSearch::getTextContent($metadata->first()));
    }

    /** @testdox GenericParser exposes discovery without changing legacy parser method names. */
    public function testGenericParserDiscoveryFacade()
    {
        $html = '<div><article class="post-card"><h2>One</h2></article><article class="post-card"><h2>Two</h2></article></div>';

        $titles = GenericParser::findElementsBySemanticNames($html, ['title'], 'html');
        $structures = GenericParser::discoverDomStructures($html, 'html');

        static::assertCount(2, $titles);
        static::assertNotEmpty($structures);
    }

    /**
     * @param array $candidates
     * @param string $class
     * @return array|null
     */
    private function findCandidateByClass(array $candidates, $class)
    {
        foreach ($candidates as $candidate) {
            if (in_array($class, $candidate['classes'], true)) {
                return $candidate;
            }
        }

        return null;
    }
}
