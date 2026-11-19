<?php

namespace Laminas\Feed\Reader\Http {
    interface ResponseInterface
    {
        public function getStatusCode();
        public function getBody();
    }

    interface ClientInterface
    {
        public function get($url);
    }

    interface HeaderAwareClientInterface extends ClientInterface
    {
        public function get($url, array $headers = []);
    }

    class FakeResponse implements ResponseInterface
    {
        private $status;
        private $body;

        public function __construct($status, $body)
        {
            $this->status = $status;
            $this->body = $body;
        }

        public function getStatusCode()
        {
            return $this->status;
        }

        public function getBody()
        {
            return $this->body;
        }
    }

    class BasicFakeClient implements ClientInterface
    {
        public $calls = 0;

        public function get($url)
        {
            $this->calls++;
            return $this->getLocalResponse($url);
        }

        protected function getLocalResponse($url)
        {
            $context = stream_context_create(['http' => ['ignore_errors' => true]]);
            $body = file_get_contents($url, false, $context);
            $headers = isset($http_response_header) ? $http_response_header : [];
            $status = 0;
            foreach ($headers as $header) {
                if (preg_match('/^HTTP\/[0-9.]+ ([0-9]{3})/', $header, $matches)) {
                    $status = (int)$matches[1];
                }
            }

            if ($status === 0 || $body === false) {
                throw new \RuntimeException('The local HTTP fake did not receive a response.');
            }

            return new FakeResponse($status, $body);
        }
    }

    class HeaderFakeClient extends BasicFakeClient implements HeaderAwareClientInterface
    {
        public $headers = [];

        public function get($url, array $headers = [])
        {
            $this->headers = $headers;
            return parent::get($url);
        }
    }
}

namespace Laminas\Http {
    if (in_array($argv[1], [
        'reader-with-http',
        'reader-with-http-cache',
        'reader-with-http-missing',
        'reader-with-http-malformed',
    ], true)) {
        class Client
        {
        }
    }
}

namespace Laminas\Feed\Reader {
    use Laminas\Feed\Reader\Http\HeaderAwareClientInterface;
    use Laminas\Feed\Reader\Http\HeaderFakeClient;

    class Reader
    {
        private static $client;
        private static $cached;
        public static $importCalls = 0;
        public static $importStringCalls = 0;

        public static function getHttpClient()
        {
            if (self::$client === null) {
                if (!class_exists('Laminas\Http\Client')) {
                    throw new \Error("Class 'Laminas\\Http\\Client' not found");
                }
                self::$client = new HeaderFakeClient();
            }

            return self::$client;
        }

        public static function setHttpClient($client)
        {
            self::$client = $client;
        }

        public static function import($url)
        {
            self::$importCalls++;
            if (self::$cached !== null && $GLOBALS['argv'][1] === 'reader-with-http-cache') {
                return self::$cached;
            }

            $client = self::getHttpClient();
            $response = $client instanceof HeaderAwareClientInterface ?
                $client->get($url, ['X-Laminas-Probe' => ['preserved']]) :
                $client->get($url);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Feed failed with code ' . $response->getStatusCode());
            }

            $feed = self::importString($response->getBody());
            $feed->parser = 'import';
            self::$cached = $feed;

            return $feed;
        }

        public static function importString($content)
        {
            self::$importStringCalls++;
            if (strpos($content, '<rss') === false ||
                strpos($content, '<title>Local RSS</title>') === false
            ) {
                throw new \RuntimeException('Feed XML is missing expected elements.');
            }

            return (object)[
                'parser' => 'importString',
                'title' => 'Local RSS',
            ];
        }
    }
}

namespace {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

    $mode = $argv[1];
    $url = $argv[2];

    if ($mode === 'reader-with-custom') {
        \Laminas\Feed\Reader\Reader::setHttpClient(new \Laminas\Feed\Reader\Http\BasicFakeClient());
    }

    $original = null;
    if ($mode !== 'reader-only') {
        $original = \Laminas\Feed\Reader\Reader::getHttpClient();
    }

    $wrapper = new \TorneLIB\Module\Network\Wrappers\RssWrapper();
    $thrown = null;
    try {
        $wrapper->request($url);
    } catch (\Exception $exception) {
        $thrown = $exception->getMessage();
    }

    $code = $wrapper->getCode();
    $body = $wrapper->getBody();
    $result = $wrapper->getParsed();
    $previousCode = $code;

    if ($mode === 'reader-with-http-cache') {
        $wrapper->request($url);
        $code = $wrapper->getCode();
        $body = $wrapper->getBody();
        $result = $wrapper->getParsed();
    }

    echo json_encode([
        'code' => $code,
        'previousCode' => $previousCode,
        'parser' => is_object($result) ? $result->parser : null,
        'title' => is_object($result) ? $result->title : null,
        'hasRawBody' => is_string($body) && strlen($body) > 0,
        'importCalls' => \Laminas\Feed\Reader\Reader::$importCalls,
        'importStringCalls' => \Laminas\Feed\Reader\Reader::$importStringCalls,
        'transportCalls' => is_object($original) ? $original->calls : null,
        'headersPreserved' => ($original instanceof \Laminas\Feed\Reader\Http\HeaderFakeClient) &&
            isset($original->headers['X-Laminas-Probe'][0]) &&
            $original->headers['X-Laminas-Probe'][0] === 'preserved',
        'originalClientRestored' => $original === null ? true :
            \Laminas\Feed\Reader\Reader::getHttpClient() === $original,
        'error' => $thrown,
    ]);
}
