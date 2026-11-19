<?php

namespace Laminas\Feed\Reader {
    /**
     * Simulate an installed optional feed parser without a network dependency.
     */
    class Reader
    {
        public static $importCount = 0;

        public static function importString($content)
        {
            self::$importCount++;

            return (object)[
                'parser' => 'laminas-feed',
                'raw' => $content,
            ];
        }
    }
}

namespace {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

    use TorneLIB\Helpers\GenericParser;
    use TorneLIB\IO\Data\Content;

    $rss = '<rss version="2.0"><channel><title>RSS</title></channel></rss>';
    $atom = '<?xml version="1.0"?><feed xmlns="http://www.w3.org/2005/Atom">' .
        '<title>Atom</title><id>urn:feed</id><updated>2026-01-01T00:00:00Z</updated></feed>';
    $rssOne = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" ' .
        'xmlns="http://purl.org/rss/1.0/"><channel rdf:about="http://example.invalid/">' .
        '<title>RSS One</title></channel></rdf:RDF>';
    $generic = '<root><value>ok</value></root>';
    $soap = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">' .
        '<soap:Body><result>ok</result></soap:Body></soap:Envelope>';
    $rdf = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">' .
        '<rdf:Description rdf:about="urn:generic"><name>Not a feed</name></rdf:Description></rdf:RDF>';
    $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
        '<url><loc>https://example.invalid/</loc></url></urlset>';
    $comments = '<?xml version="1.0"?><!-- feed header --><rss><channel>' .
        '<title>RSS with header</title></channel></rss>';

    $cases = [
        'generic-xml' => [$generic, 'application/xml', false],
        'generic-text-xml' => [$generic, 'text/xml', false],
        'generic-xml-alias' => [$generic, 'xml', false],
        'generic-vendor-xml' => [$generic, 'application/vnd.example+xml', false],
        'generic-atom-mimetype' => [$generic, 'application/atom+xml', true],
        'soap' => [$soap, 'application/soap+xml', false],
        'sitemap' => [$sitemap, 'application/xml', false],
        'generic-rdf' => [$rdf, 'application/xml', false],
        'fake-rss-namespace' => ['<rss xmlns="urn:not:rss"><value>x</value></rss>', 'application/xml', false],
        'nested-rss' => ['<root><rss><channel/></rss></root>', 'application/xml', false],
        'rss-content' => [$rss, 'application/xml', true],
        'rss-xml-alias' => [$rss, 'xml', true],
        'rss-feed-mimetype' => [$rss, 'application/rss+xml', true],
        'rss-headers' => [$comments, 'application/xml; charset=utf-8', true],
        'atom-content' => [$atom, 'application/xml', true],
        'atom-feed-mimetype' => [$atom, 'application/atom+xml', true],
        'rss-one' => [$rssOne, 'application/rdf+xml', true],
        'html-unchanged' => [$generic, 'html', false],
    ];

    libxml_use_internal_errors(false);
    $responses = [];

    foreach ($cases as $key => $case) {
        $before = ReaderMarker();
        $actual = GenericParser::getParsed($case[0], $case[1]);
        $after = ReaderMarker();
        $usedLaminas = $after === $before + 1;
        $equivalent = null;

        if (!$case[2] && $case[1] !== 'html') {
            $expected = (new Content())->getFromXml($case[0]);
            $equivalent = serialize($actual) === serialize($expected);
        } elseif ($case[1] === 'html') {
            $equivalent = $actual === $case[0];
        }

        $responses[$key] = [
            'usedLaminas' => $usedLaminas,
            'expectLaminas' => $case[2],
            'sameLegacyXml' => $equivalent,
            'errorModeRestored' => libxml_use_internal_errors() === false,
        ];
    }

    echo json_encode($responses);

    function ReaderMarker()
    {
        return \Laminas\Feed\Reader\Reader::$importCount;
    }
}
