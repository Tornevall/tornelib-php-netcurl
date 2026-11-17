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

## Compatibility

The old PHP 5.6 support claim is no longer valid for the current 6.1 source tree.

Runtime compatibility is verified by the maintained GitHub Actions compatibility matrix. Historical Bamboo and Bitbucket runs can still be useful when investigating old releases, but they must not be treated as the current compatibility reference.

- [GitHub Actions](https://github.com/Tornevall/tornelib-php-netcurl/actions)

Compatibility work and legacy probes are tracked in:

- [#14 - Expand PHP compatibility CI matrix](https://github.com/Tornevall/tornelib-php-netcurl/issues/14)
- [#20 - Expand and stabilize the NetCurl 6.1 test suite](https://github.com/Tornevall/tornelib-php-netcurl/issues/20)

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

## Changelog policy

From the current maintenance work onward, changelog entries for features, fixes and compatibility changes link to their GitHub issue/ticket. Historical changelog entries are kept as historical records and are not assigned guessed ticket references.

See [CHANGELOG.md](CHANGELOG.md).
