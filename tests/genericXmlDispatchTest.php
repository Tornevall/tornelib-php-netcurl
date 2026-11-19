<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Helpers\GenericParser;
use TorneLIB\IO\Data\Content;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Regression coverage for generic XML and optional RSS/Atom dispatch.
 */
class genericXmlDispatchTest extends TestCase
{
    public function testGenericXmlAlwaysPreservesTheLegacyStructure()
    {
        $cases = [
            ['<root><value>ok</value></root>', 'application/xml'],
            ['<root><value>ok</value></root>', 'text/xml'],
            ['<root><value>ok</value></root>', 'xml'],
            ['<root><value>ok</value></root>', 'application/vnd.example+xml'],
            [
                '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">' .
                '<soap:Body><value>ok</value></soap:Body></soap:Envelope>',
                'application/soap+xml'
            ],
            [
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
                '<url><loc>https://example.invalid/</loc></url></urlset>',
                'application/xml'
            ],
        ];

        foreach ($cases as $case) {
            $expected = (new Content())->getFromXml($case[0]);
            $actual = GenericParser::getParsed($case[0], $case[1]);

            static::assertSame(
                serialize($expected),
                serialize($actual),
                'Generic XML structure changed for ' . $case[1]
            );
        }
    }

    public function testRssAndAtomWithoutAnInstalledFeedReaderUseTheGenericFallback()
    {
        if (class_exists('Laminas\Feed\Reader\Reader')) {
            static::markTestSkipped('This fallback check requires an installation without Laminas Feed Reader.');
        }

        $cases = [
            ['<rss><channel><title>Local RSS</title></channel></rss>', 'application/xml'],
            [
                '<feed xmlns="http://www.w3.org/2005/Atom"><title>Local Atom</title></feed>',
                'application/atom+xml'
            ],
        ];

        foreach ($cases as $case) {
            $expected = (new Content())->getFromXml($case[0]);
            $actual = GenericParser::getParsed($case[0], $case[1]);

            static::assertSame(serialize($expected), serialize($actual));
        }
    }

    public function testInstalledFeedReaderOnlyReceivesVerifiedFeedXml()
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('proc_open')) {
            static::markTestSkipped('Isolated parser probe requires POSIX process support.');
        }

        $command = escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg(__DIR__ . '/fixtures/xml-feed-parser-probe.php');
        $pipes = [];
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        static::assertTrue(is_resource($process), 'Unable to launch isolated Laminas parser fixture.');
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        static::assertSame(0, $status, 'XML parser probe failed: ' . $errors);
        $results = json_decode($output, true);
        static::assertTrue(is_array($results), 'Invalid parser probe output: ' . $output);
        static::assertCount(18, $results);

        foreach ($results as $name => $result) {
            static::assertSame(
                $result['expectLaminas'],
                $result['usedLaminas'],
                'Wrong parser selected for ' . $name
            );
            if (!$result['expectLaminas']) {
                static::assertTrue($result['sameLegacyXml'], 'Legacy return shape changed: ' . $name);
            }
            static::assertTrue($result['errorModeRestored'], 'libxml error mode leaked: ' . $name);
        }
    }
}
