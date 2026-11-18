# Changelog policy

From this maintenance work onward, every changelog item that represents a feature, fix, compatibility change or other trackable implementation work must link to its GitHub issue/ticket.

Historical entries below are kept as historical records. Do not invent or guess issue mappings for old entries where a reliable ticket reference is unavailable.

# Unreleased

## Fixes

* Reset request payloads for every call, including empty arrays, empty strings and null, in configuration, cURL and stream transports; restore GET mode after a POST without resending the old body. [#37](https://github.com/Tornevall/tornelib-php-netcurl/issues/37)
* Isolate built-in transport response and configuration state by constructing a fresh driver for each internal request, while retaining static driver availability discovery and caller-owned registered external drivers. [#36](https://github.com/Tornevall/tornelib-php-netcurl/issues/36)
* Keep cURL multi-response headers associated with their actual handles on PHP 7 (resources) and PHP 8+ (objects), using strict identity instead of integer casting; unknown handles no longer pollute another request's headers. [#35](https://github.com/Tornevall/tornelib-php-netcurl/issues/35)
* Restore strict cURL certificate and hostname verification after an SSL policy changes from relaxed to strict on a reused configuration; preserve explicit legacy cURL overrides until the SSL policy changes. [#34](https://github.com/Tornevall/tornelib-php-netcurl/issues/34)

## Testing

* Verify reused cURL and stream clients send the expected method and raw request body across nonempty/empty POST, POST-to-GET, empty JSON/XML and explicit empty-string transitions against a localhost HTTP fixture. [#37](https://github.com/Tornevall/tornelib-php-netcurl/issues/37)
* Add deterministic driver isolation, interleaved localhost HTTP-response/configuration, and external driver registration/priority regression coverage. [#36](https://github.com/Tornevall/tornelib-php-netcurl/issues/36)
* Add deterministic handle-identity and local HTTP multi-request tests for distinct headers, HTTP status codes, parsed bodies, and repeated URLs across PHP versions. [#35](https://github.com/Tornevall/tornelib-php-netcurl/issues/35)
* Add deterministic regression coverage for repeated strict/relaxed TLS transitions, independent peer and host settings, configuration replacement, and direct cURL option compatibility. [#34](https://github.com/Tornevall/tornelib-php-netcurl/issues/34)

# 6.1.11

## Updates

* Release NetCurl 6.1.11 with unchanged 6.1 dependency constraints, additive DOM discovery APIs and the expanded PHP compatibility matrix. [#12](https://github.com/Tornevall/tornelib-php-netcurl/issues/12) [#14](https://github.com/Tornevall/tornelib-php-netcurl/issues/14)

## Features

* Add opt-in, traversable XML/HTML DOM document/node models, semantic discovery, reusable XPath candidates and the additive `GenericParser` facade while preserving the existing `SimpleDomParser` contract. [#12](https://github.com/Tornevall/tornelib-php-netcurl/issues/12)

## Fixes

* Fix PHP 7.2 DOM/XPath counting by using capability detection instead of calling `is_countable()` where it is unavailable. [#17](https://github.com/Tornevall/tornelib-php-netcurl/issues/17)
* Allow `WrapperConfig`/`NetWrapper` to initialize without the cURL extension so automatic stream fallback can actually occur. [#23](https://github.com/Tornevall/tornelib-php-netcurl/issues/23)

## Testing

* Keep new DOM regression tests compatible with PHPUnit 6 on the PHP 7.0 legacy probe, without changing application code or test expectations. [#31](https://github.com/Tornevall/tornelib-php-netcurl/issues/31)
* Recognize libcurl certificate verification error 60 alongside legacy SSL codes and skip the external SSL-mismatch fixture test on DNS/connect/timeout errors instead of reporting unrelated network outages as regressions. [#20](https://github.com/Tornevall/tornelib-php-netcurl/issues/20)
* Expand the maintained 6.1 suite with deterministic configuration, input/output, driver-registration and compatibility-contract coverage. [#20](https://github.com/Tornevall/tornelib-php-netcurl/issues/20) [#22](https://github.com/Tornevall/tornelib-php-netcurl/issues/22)
* Expand blocking PHP CI to 7.3-8.5, add non-blocking legacy 7.0-7.2 and PHP 8.6 compatibility probes, and test no-cURL/no-SOAP fallback against local HTTP fixtures. Remove the obsolete PHP 5.6 syntax probe after 5.6 support was withdrawn. [#14](https://github.com/Tornevall/tornelib-php-netcurl/issues/14)
* Normalize SSL fixture verification failures vs DNS/connect/timeout errors without weakening the actual certificate checks; unavailable external fixtures now skip rather than fail the compatibility matrix. [#20](https://github.com/Tornevall/tornelib-php-netcurl/issues/20)

## Documentation

* Remove the stale PHP 5.6 compatibility claim from maintained documentation. [#25](https://github.com/Tornevall/tornelib-php-netcurl/issues/25)
* Move the maintained public documentation target from old Confluence pages to Tornevall Tools and require issue-linked changelog entries going forward. [#27](https://github.com/Tornevall/tornelib-php-netcurl/issues/27)

# 6.1.10

## Fixes

* Hardened `SimpleDomParser` against null DOM nodes under PHP 8+, so partially matching XPath trees no longer crash on `method_exists(..., null)` during compiled XPath extraction.

# 6.1.9

## Updates

* Synchronized the library's internal version metadata with the 6.1.9 release line after earlier tag/version drift.
* Refreshed CI/build configuration for the maintained 6.1 branch, including Bamboo / GitHub workflow updates and newer PHP coverage adjustments.

## Fixes

* Cleaned up and reduced stale test coverage that no longer matched the maintained 6.1 branch.
* Removed `phpunit` from package metadata again to avoid CI/environment breakage in dependency resolution.

# 6.1.7

## Updates

* Landed in this release together with follow-up cleanup around the 6.1.7 tag.
* Converted and cleaned PHPUnit XML/test configuration for the maintained 6.1 branch.

## Fixes

* Fixed version reporting (`getMyVersion`) so package/runtime version lookup stays aligned with the tagged release.

# 6.1.6

## Updates

* Wrapper timing/request handling adjustments landed for the 6.1.6 line.
* Dropped PHP 5.6 support in the maintained 6.1 branch.

## Fixes

* Improved stream-wrapper handling around query results and timeout-related edge cases.
* Added/fixed regression coverage in curl/netwrapper/simple-stream tests for the 6.1.6 release.

# 6.1.5

## Updates

* Introduced the timeout-handler work for this release.
* Additional wrapper/config fixes landed for the 6.1.5 line.
* Follow-up fixes from an earlier issue were included in this release.
* PHP compatibility/docblock cleanup was included in this release.
* Spelling/docblock cleanup and related fixes were included in this release.

## Fixes

* Fixed an earlier reported issue.
* Corrected microtime/time handling and related failing tests.
* Added the `SimpleDomParser`/compiled XPath helper flow together with DOM/XPath regression coverage and fixture data.
* Reworked XPath value extraction so requested attributes/values can be taken from the configured main/sub-node container.

# 6.1.4

## Updates

* Added `PATCH` request-method support and cleaned up redundant method constants.
* MultiCurl now supports several identical requests and per-client header handling.
* Added the typed `RequestMethod` class and normalized several `Model\Type` class names to PSR-friendly casing.

## Fixes

* Inspection-driven cleanup and compatibility fixes across wrappers/configuration.
* Restored resource handling for PHP-version differences and corrected body/resource lookup edge cases.
* Updated README, pipelines, Psalm config, and regression tests to match the 6.1.4 codebase.

# 6.1.3

## Updates

* `setSignature` may crash `setUserAgent` if an array is sent into the merger, and overwrite/write protection was opened up.
* Timeout defaults should be flaggable and support milliseconds, while native timeout support should also be passed through the configuration.
* Avoid using flaggables for timeouts in tests.
* Prevent unexpected results in unprepared environments.

# 6.1.2

## Updates

* Verify emptiness only.
* Change the way netcurl returns package version information.
* Simplify `setCurlHeader` again.
* `resetCurlRequest()` should not reset custom headers on demand.
* Bitbucket pipelines for PHP 8.
* Default internal timeout must be higher than 8, since 4 for connection timeouts is too low.

## Fixes

* Fixed: `getParsedResponse` is used in rare cases (`t-auth`).
* Fixed: deprecated curldriver was missing proper PHP 8 support.
* Fixed: invisible exit code with `unit70` for the `wsdlcache` test.
* Fixed: fetching version falsely returned the wrong version.
* Fixed: version data falsely reported `6.1.0` when `composer.json` was missing.

# 6.1.1

## Updates

* Use centralized version checker as most of the libraries are only compatible with 5.6 and above.

## Fixes

* Fixed: memory exhaustion on the lowest PHP memory limit (resources are no longer resources).

# 6.1.0

## Transformed

* Confirm (by driver) that a driver is really available (update interface with requirements).
* Support stream when curl is not an option.
* Migrate `getHttpHost()`.
* Move out `MODULE_NETWORK` to its own repo.
* Make exceptions global.
* Support immediate inclusions of network libraries.
* Make `setTimeout` support milliseconds (`curlopt_timeout_ms`).
* Disengage from constructor usage.
* Reimport SSL helper.
* Reimport curl module.
* Reimport `soapclient`.
* The way the SSL module sets user agent must be able to set it in parents.
* Add `setAuth` for curl.
* Add `setAuth` for soap.
* Add a static list of browsers for user agent.
* Add `NetWrapper` MultiRequest.
* Put high focus on curl (rebuild from 6.0).
* The current curl implementation was only using GET and had no advantage of config.
* Make sure `setAuthentication` is a required standard in the wrapper interface.
* Add errorhandler for multicurl.
* Avoid static constants inside core functions.
* `getCurlException($curlHandle, $httpCode)` had an unused `httpCode`; now throws on `>400`.
* Handle HTTP head errors (`>400`) and non-empty bodies.
* Add timeouts.
* Add proxy support for `stream_context`.
* Synchronize with netcurl 6.0 test suites.
* Support driverless environment.
* Use a natural soap call with `call_user_func_array`.
* Add `Netwrapper` Compatibility Service.
* `setChain` in 6.1 should throw errors when requested true.
* Make sure `setOption` is useful in `NetWrapper` and `MODULE_CURL`.
* Add proxy support for curlwrapper and wrappers that are not stream wrappers.
* Use `setSignature` to make requesting clients set internal client name/version as user agent automatically instead of Mozilla.
* Reinstate Environment in `ConfigWrapper` so WSDL transfers can go non-cache vs cache, etc.
* Move driver handler into its own class.
* Initialize simplified `streamSupport`.
* Add output support for XML in simpler wrappers.
* Open for third-party identification rather than standard browser agent.
* `SoapClient` must be reinitialized each time it is called.
* Support basic `rss+xml` via `GenericParser`.
* Try to fix proper RSS parsing without garbage.
* Make it possible to initialize an empty curlwrapper (without URL).
* Add `MultiNetwrapper` (+Soap).

## Fixes

* Fixed: PSR4 NetCURL+Network (Phase 1).
* Fixed: WordPress driver in prior netcurl lacked authentication mechanisms.
* Fixed: pipeline errors for PHP 7.3-7.4.
* Fixed: `getSoapEmbeddedRequest()` for PHP 5.6 + PHP 7.0.
* Fixed: cached WSDL requests and unauthorized exceptions.
