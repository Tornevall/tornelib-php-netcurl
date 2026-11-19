# NETCURL 6.1

NetCurl is a transport-flexible PHP communication library. Its core purpose is to let callers make a request without hard-coding whether cURL, PHP streams, SOAP, RSS/XML, or a registered custom driver must perform it.

## Installation

Recommended standalone installation:

```bash
composer require tornevall/tornelib-php-netcurl:^6.1
```

If you also need the separate network/domain/address utility package:

```bash
composer require tornevall/tornelib-php-network:^6.1
```

## Documentation

The maintained public documentation now lives in Tornevall Tools:

- [NetCurl documentation - English](https://tools.tornevall.net/docs/en/netcurl)
- [NetCurl documentation - Swedish](https://tools.tornevall.net/docs/sv/netcurl)

The guide contains practical examples for normal requests, JSON/XML, SOAP/WSDL, RSS/XML, raw/parsed responses, headers, authentication, proxies, timeouts, multi-request handling, custom drivers, and migration away from `MODULE_CURL`.

Implementation history, defects and version planning are tracked in GitHub:

- [NetCurl issues](https://github.com/Tornevall/tornelib-php-netcurl/issues)
- [6.1 test expansion](https://github.com/Tornevall/tornelib-php-netcurl/issues/20)
- [Cross-version compatibility contract](https://github.com/Tornevall/tornelib-php-netcurl/issues/22)

Old Confluence documentation is no longer the maintained documentation target.

## Opt-in structured DOM parsing and discovery

These APIs are introduced in NetCurl 6.1.11 (upon tagging/release). Existing
`^6.1` Composer consumers remain on the same major/minor constraint. NetCurl
still exposes the legacy `NetWrapper`, `MODULE_CURL` compatibility facade and
`SimpleDomParser` methods; none of these paths are replaced by DOM discovery.

The structured DOM API is additive to the existing XPath parser. Existing calls to
`SimpleDomParser::getFromXPath()`, `SimpleDomParser::getContentFromXPath()`, and
`GenericParser` legacy methods preserve their previous return structures.

```php
use TorneLIB\Helpers\GenericParser;

$html = '<article class="news-card"><h2>Example</h2></article>';
$document = GenericParser::getDocumentModel($html, 'html');
$matches = $document->query('//h2');
$inventory = GenericParser::getDomElementInventory($html, 'html');
$repeating = GenericParser::discoverDomStructures($html, 'html');
$titles = GenericParser::findElementsBySemanticNames($html, ['title'], 'html');
```

`getDocumentModel()` accepts `auto`, `xml`, and `html`. The document and
node models support traversal, XPath queries, namespaced XML and array/JSON
serialization. `DomSemanticSearch` supplies generic semantic element discovery,
element inventories, and repeated-structure candidates. It does not extract or
store application-specific RSS data. Structured parsing must be explicitly
selected; it does not replace the legacy XPath implementation.

Parsing is local, disables external network entity fetching, and restores the
previous libxml error mode. The DOM tests include legacy compatibility, real-site
HTML snapshots, namespaces, malformed documents and direct attribute XPath.

See [issue #12](https://github.com/Tornevall/tornelib-php-netcurl/issues/12)
and [PR #13](https://github.com/Tornevall/tornelib-php-netcurl/pull/13)
for scope and implementation details.

## cURL, streams, SOAP and XML

cURL is the preferred general HTTP driver when available, but it is not supposed to be a hard requirement for generic requests. NetCurl is designed to select the best usable driver at runtime.

A typical installation may include:

```bash
apt-get install php-curl php-xml php-soap
```

Relevant runtime capabilities:

- cURL: preferred general HTTP transport when available.
- PHP streams / `allow_url_fopen`: fallback path for compatible HTTP requests when cURL is unavailable.
- SOAP: `SoapClient` for WSDL/SOAP requests when available.
- XML: used for XML/SOAP-related parsing and fallback paths.
- RSS/XML: supported through the RSS wrapper and parsing layer.
- External drivers: applications can register implementations of the wrapper interface and choose whether they run before or after built-in drivers.

See the Tools documentation for examples and exact behavior.

## GET query parameters

Ordinary `RequestMethod::GET` requests with `DataType::NORMAL` now place their parameters in the URL, not the request body, for cURL, PHP streams, and cURL multi requests. Keys/values are form-encoded using `http_build_query()`, including nested/repeated array values. Existing query strings (even repeated keys) are kept intact, new fields are appended before URL fragments, and an empty payload adds nothing. A raw, already-encoded query string such as `'a=1&a=2'` is also supported. For example:

```php
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Network\NetWrapper;

$response = (new NetWrapper())->request(
    'https://example.org/items?filter=active#list',
    ['page' => 2, 'term' => 'a & b'],
    RequestMethod::GET
);
// Effective URL: https://example.org/items?filter=active&page=2&term=a+%26+b#list
// No GET request body.
```

Requests explicitly selecting `DataType::JSON` or `DataType::XML` on GET retain their legacy raw-body handling, rather than being converted to ordinary URL parameters. Raw GET bodies can be unsupported or rejected by intermediaries and should be avoided where possible. POST, PUT, and PATCH continue sending their payloads in the body. See [#38](https://github.com/Tornevall/tornelib-php-netcurl/issues/38).

## Request payload isolation on reused clients

Each `request()` call replaces the previous request data, including when the supplied data is `[]`, `''` or `null`. A normal empty array represents an empty body; JSON empty arrays are encoded as `[]`. Switching from POST to an empty GET resets cURL's POST mode rather than retaining the previous request body; explicitly selected JSON/XML GET bodies continue to use their legacy handling as described above. Persistent client configuration (such as authentication and timeout) remains available. This is covered by local HTTP fixture regression tests for cURL and PHP streams. See [#37](https://github.com/Tornevall/tornelib-php-netcurl/issues/37).

## Independent transport state

Each internal request through `NetWrapper` uses a fresh built-in transport instance. Available drivers are discovered globally, but response bodies, status codes, headers and request configuration are not shared between independent `NetWrapper` clients or successive requests. A previously returned transport response remains available after another request runs. Caller-registered external transport objects preserve their existing registration and priority semantics and are not copied or reconstructed. See [#36](https://github.com/Tornevall/tornelib-php-netcurl/issues/36).

## cURL multi-request response isolation

When calling `CurlWrapper::request()` with multiple URLs, cURL tracks each simultaneous handle separately on PHP 7 and PHP 8+. Response headers and status codes remain associated with their respective request indices, including when the same URL is requested more than once. Use `getHeader($headerName, $requestIndex)` and `getCode($requestIndex)` to distinguish duplicate URLs; URL-based header lookup identifies the first matching entry. Bodies can be consumed in request order with `getParsed()`. Regression coverage uses a local HTTP server without external DNS. See [#35](https://github.com/Tornevall/tornelib-php-netcurl/issues/35).

## TLS verification

NetCurl uses strict certificate and hostname verification by default for cURL and stream connections. Use `$wrapper->getConfig()->getSsl()->setStrictVerification(false)` only when an explicit, temporary relaxation is required, and re-enable verification with `setStrictVerification(true)`. On a reused configuration, changing the SSL policy now updates cURL peer and host verification in both directions; legacy cURL options explicitly set through `WrapperConfig::setOption()` are left alone until the SSL policy changes. See [#34](https://github.com/Tornevall/tornelib-php-netcurl/issues/34).

## Compatibility

The old PHP 5.6 support claim is no longer valid for the current 6.1 source tree.

Runtime compatibility is verified by the maintained GitHub Actions compatibility matrix. Historical Bamboo and Bitbucket runs can still be useful when investigating old releases, but they must not be treated as the current compatibility reference.

- [GitHub Actions](https://github.com/Tornevall/tornelib-php-netcurl/actions)

Compatibility work and legacy probes are tracked in:

- [#14 - Expand PHP compatibility CI matrix](https://github.com/Tornevall/tornelib-php-netcurl/issues/14)
- [#20 - Expand and stabilize the NetCurl 6.1 test suite](https://github.com/Tornevall/tornelib-php-netcurl/issues/20)

## PHP compatibility CI

The blocking PHP matrix tests 7.3, 7.4 and every PHP 8 release from 8.0
through 8.5. The separate PHP 8.6 job is a non-blocking forward-compatibility
probe while PHP 8.6 is in release-candidate status.

PHP 7.0-7.2 run as non-blocking legacy probes. PHP 5.6 is not supported
by the maintained 6.1 source and is not included in the matrix.

Two PHP 8.3 capability jobs explicitly disable cURL and SOAP separately
and verify the corresponding automatic transport fallback against a local
HTTP fixture. These tests do not require public DNS.

The legacy full PHPUnit suite also contains live-network tests using
`ipv4.fraudbl.org` for IP/HTTP responses and separate Tornevall SSL
fixtures. Availability-related failures in those tests are distinct from
deterministic driver and parser regressions. Obsolete netcurl.org IP-check
URLs are not used by the maintained test suite.

See [#14](https://github.com/Tornevall/tornelib-php-netcurl/issues/14)
for the version matrix and [#20](https://github.com/Tornevall/tornelib-php-netcurl/issues/20)
for ongoing deterministic test coverage.

## Current built-in driver support

- cURL
- PHP stream/file-get-contents transport
- SoapClient
- RSS/XML
- externally registered custom drivers

Sockets are not currently a built-in NetCurl transport.

## RSS dependencies

For richer RSS feed support, install Laminas Feed:

```bash
composer require laminas/laminas-feed
```

NetCurl keeps fallback behavior for RSS/XML so optional feed libraries do not automatically become hard requirements.

## `MODULE_CURL`

`MODULE_CURL` is a legacy compatibility facade retained in 6.1 for older clients. New 6.1 code should prefer `TorneLIB\Module\Network\NetWrapper`.

`MODULE_CURL` is deprecated in 6.1 and planned for removal in 6.2. Compatibility work is deliberately preserving the useful request/driver/input/output behavior rather than preserving the old class forever.

See:

- [#16 - Remove MODULE_CURL in 6.2 and consolidate the networking API](https://github.com/Tornevall/tornelib-php-netcurl/issues/16)
- [#22 - Define the cross-version NetCurl compatibility contract](https://github.com/Tornevall/tornelib-php-netcurl/issues/22)

## Release tagging

Releases are based on an explicit matching version in `composer.json` and the
first numbered release section in `CHANGELOG.md`. When a commit on `master`
passes the `PHP Composer` workflow, the dedicated release workflow checks that
the tested commit is still the latest `master` commit and that its version tag
does not already exist. Only then does it create the GitHub tag and release using
the changelog section as release notes.

This keeps source changes and unverified PR commits from being tagged. The
existing `^6.1` dependency constraint remains compatible with 6.1.11.

Release tracking: [#31](https://github.com/Tornevall/tornelib-php-netcurl/issues/31).

## Changelog policy

From the current maintenance work onward, changelog entries for features, fixes and compatibility changes link to their GitHub issue/ticket. Historical changelog entries are kept as historical records and are not assigned guessed ticket references.

See [CHANGELOG.md](CHANGELOG.md).
