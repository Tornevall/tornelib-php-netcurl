<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\RssWrapper;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Deterministic RSS transport status tests without public feed dependencies.
 */
class rssHttpStatusTest extends TestCase
{
    public function testParserOnlyAndUnrequestedWrapperHaveNoHttpStatus()
    {
        $wrapper = new RssWrapper();
        static::assertNull($wrapper->getCode());

        $wrapper->getParsed(
            '<rss version="2.0"><channel><title>String only</title>' .
            '<description>Only parsing</description></channel></rss>'
        );
        static::assertNull($wrapper->getCode());
    }

    public function testSuccessfulRssAndAtomKeepRealHttpStatus()
    {
        $this->withLocalServer(function ($base) {
            $wrapper = new RssWrapper();

            $rss = $wrapper->request($base . '?case=rss');
            static::assertSame($wrapper, $rss);
            static::assertSame(200, $wrapper->getCode());
            static::assertNotEmpty($wrapper->getBody());
            static::assertNotNull($wrapper->getParsed());

            $atom = $wrapper->request($base . '?case=atom');
            static::assertSame($wrapper, $atom);
            static::assertSame(200, $wrapper->getCode());
            static::assertNotFalse(strpos($wrapper->getBody(), 'Local Atom'));
            static::assertNotNull($wrapper->getParsed());

            $netWrapper = new NetWrapper();
            $netWrapper->request($base . '?case=rss', [], RequestMethod::GET, DataType::RSS_XML);
            static::assertSame(200, $netWrapper->getCode());
            static::assertNotEmpty($netWrapper->getBody());
        });
    }

    public function testHttpFailuresNeverBecomeInventedSuccess()
    {
        if (!extension_loaded('curl')) {
            static::markTestSkipped('Precise HTTP error codes require the cURL transport in this test.');
        }

        $this->withLocalServer(function ($base) {
            $wrapper = new RssWrapper();
            $wrapper->request($base . '?case=rss');
            static::assertSame(200, $wrapper->getCode());

            foreach (['missing' => 404, 'failure' => 503] as $case => $status) {
                try {
                    $wrapper->request($base . '?case=' . $case);
                } catch (\Exception $expectedHttpError) {
                    // The transport may throw for error HTTP responses.
                }
                static::assertSame($status, $wrapper->getCode(), 'Wrong status for ' . $case);
            }

            $wrapper->request($base . '?case=rss');
            static::assertSame(200, $wrapper->getCode());
        });
    }

    public function testMalformedResponseKeepsHttpStatusSeparateFromParsing()
    {
        $this->withLocalServer(function ($base) {
            $wrapper = new RssWrapper();
            try {
                $wrapper->request($base . '?case=malformed');
            } catch (\Exception $expectedParseError) {
                // Parsing errors do not turn a successfully received HTTP response into 404.
            }

            static::assertSame(200, $wrapper->getCode());
            static::assertSame('<rss><channel><title>Malformed', $wrapper->getBody());
        });
    }

    public function testOptionalLaminasDependencyCombinationsPreserveOwnership()
    {
        if (!function_exists('proc_open')) {
            static::markTestSkipped('Subprocess testing requires proc_open.');
        }

        $this->withLocalServer(function ($base) {
            $cases = [
                ['reader-only', 'importString', 0, null, false],
                ['reader-with-http', 'import', 1, 1, true],
                ['reader-with-custom', 'import', 1, 1, false],
            ];

            foreach ($cases as $case) {
                $result = $this->runDependencyProbe($case[0], $base . '?case=rss');
                static::assertSame(200, $result->code, $case[0] . ' HTTP status');
                static::assertSame($case[1], $result->parser, $case[0] . ' import path');
                static::assertSame('Local RSS', $result->title);
                static::assertSame($case[2], $result->importCalls, 'Reader::import count');
                static::assertSame($case[3], $result->transportCalls, 'Original HTTP client calls');
                static::assertSame($case[4], $result->headersPreserved, 'Conditional headers');
                static::assertTrue($result->hasRawBody, 'Raw downloaded body was not retained');
                static::assertTrue($result->originalClientRestored, 'Laminas HTTP client not restored');
                static::assertNull($result->error);
            }
        });
    }

    public function testLaminasCacheHitDoesNotInventASecondHttpStatus()
    {
        $this->withLocalServer(function ($base) {
            $result = $this->runDependencyProbe('reader-with-http-cache', $base . '?case=rss');

            static::assertSame(200, $result->previousCode);
            static::assertNull($result->code);
            static::assertFalse($result->hasRawBody);
            static::assertSame('import', $result->parser);
            static::assertSame('Local RSS', $result->title);
            static::assertSame(2, $result->importCalls);
            static::assertSame(1, $result->transportCalls);
            static::assertTrue($result->originalClientRestored);
        });
    }

    public function testLaminasHttpErrorsAndMalformedFeedsKeepObservedStatus()
    {
        $this->withLocalServer(function ($base) {
            foreach ([
                ['reader-with-http-missing', 'missing', 404],
                ['reader-with-http-missing', 'failure', 503],
                ['reader-with-http-malformed', 'malformed', 200],
            ] as $case) {
                $result = $this->runDependencyProbe($case[0], $base . '?case=' . $case[1]);
                static::assertSame($case[2], $result->code, $case[1] . ' status');
                static::assertSame(1, $result->importCalls);
                static::assertSame(1, $result->transportCalls);
                static::assertTrue($result->hasRawBody);
                static::assertTrue($result->originalClientRestored);
                static::assertNotEmpty($result->error, 'Expected a Laminas import exception');
            }
        });
    }

    private function runDependencyProbe($mode, $url)
    {
        $args = escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg(__DIR__ . '/fixtures/rss-dependency-probe.php') . ' ' .
            escapeshellarg($mode) . ' ' . escapeshellarg($url);
        $pipes = [];
        $process = proc_open($args, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        static::assertTrue(is_resource($process), 'Could not start optional Reader probe.');
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        static::assertSame(0, $exit, $mode . ': ' . $stderr);
        $result = json_decode($stdout);
        static::assertTrue(is_object($result), $mode . ': Invalid JSON ' . $stdout);

        return $result;
    }

    private function withLocalServer($callback)
    {
        if (DIRECTORY_SEPARATOR === '\\' ||
            !function_exists('proc_open') ||
            !function_exists('stream_socket_server') ||
            !function_exists('fsockopen')
        ) {
            static::markTestSkipped('Local HTTP fixture requires POSIX process and socket support.');
        }

        $listener = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errorMessage);
        static::assertNotFalse($listener, 'Unable to reserve localhost port: ' . $errorMessage);
        $address = stream_socket_get_name($listener, false);
        $port = (int)substr($address, strrpos($address, ':') + 1);
        fclose($listener);

        $command = 'exec ' . escapeshellarg(PHP_BINARY) .
            ' -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg(__DIR__ . '/fixtures');
        $pipes = [];
        $server = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        static::assertTrue(is_resource($server), 'Could not start local RSS fixture server.');

        try {
            $ready = false;
            for ($attempt = 0; $attempt < 40; $attempt++) {
                $connection = @fsockopen('127.0.0.1', $port, $socketError, $socketMessage, 0.1);
                if ($connection) {
                    fclose($connection);
                    $ready = true;
                    break;
                }
                usleep(50000);
            }
            static::assertTrue($ready, 'Local RSS fixture did not become ready.');
            call_user_func($callback, 'http://127.0.0.1:' . $port . '/rss-response.php');
        } finally {
            proc_terminate($server);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($server);
        }
    }
}
