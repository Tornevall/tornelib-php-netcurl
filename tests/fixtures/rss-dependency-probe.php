<?php

namespace Laminas\Feed\Reader {
    /**
     * Minimal isolated probe for optional Reader integration.
     */
    class Reader
    {
        public static function import($url)
        {
            throw new \RuntimeException('Direct Reader::import() bypasses the HTTP status transport.');
        }

        public static function importString($content)
        {
            if (strpos($content, '<rss') === false || strpos($content, '<title>Local RSS</title>') === false) {
                throw new \RuntimeException('Unexpected RSS fixture passed to Reader::importString().');
            }

            return (object)[
                'parser' => 'importString',
                'title' => 'Local RSS',
            ];
        }
    }
}

namespace Laminas\Http {
    // The second mode simulates both optional Laminas modules being installed.
    if (isset($argv[1]) && $argv[1] === 'reader-with-http') {
        class Client
        {
        }
    }
}

namespace {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

    $wrapper = new \TorneLIB\Module\Network\Wrappers\RssWrapper();
    $wrapper->request($argv[2]);
    $result = $wrapper->getParsed();

    echo json_encode([
        'code' => $wrapper->getCode(),
        'parser' => $result->parser,
        'title' => $result->title,
        'hasRawBody' => is_string($wrapper->getBody()) && strlen($wrapper->getBody()) > 0,
    ]);
}
