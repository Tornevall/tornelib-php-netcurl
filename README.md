# NETCURL 6.1-series

[Full documents are located here](https://docs.tornevall.net/x/KwCy)


## Compatibility span (Supported PHP versions)

This library is compatible with PHP releases up to PHP 7.3. However, it is no longer entire clear on how far back in history we actually reach. PHP 5.3 has been confirmed, but there are no longer any guarantee for anything to work with PHP-releases where the official support has been expired for several years.

## Self compatibility

This library should (or probably must) be compatible with NetCURL 6.0 - NetCURL 6.1 is just a better cleaned up release of 6.0 as I'm convinced that data can be picked up differently and faster than in the prior version. Besides of this, there's a need of far better way handling customized ip interfaces since this module will be part of the upcoming project NETFILTER and a anti proxy scanner.

### Requirements and dependencies

In its initial state, there shoule not be any requirement as this module should find its own way out on the internet. Prior versions of NETCURL had basic requirements in CURL (that's why it's called NETCURL). However, CURL support should not actually be required as there are fallbacks to internal PHP functions where it is necessary.

### What NETCURL should support

* Streams
* Sockets
* Curl
* SOAP
* Possibly RSS fetching (this will require additional components in composer.json)

#### What if-dependencies

* SOAP: SoapClient and XML-drivers.
* Secure http requests: openssl or similar ssl drivers is required.

#### What NETCURL picks up

If there's traces of the below drivers, NETCURL tries to pick them up on demand, to use them:

* Guzzle
* Wordpress 

## Installation

Recommended solution: Composer.

Alternatives: git clone this repo.

#### XML, CURL, SOAP

In apt-based systems, extra libraries can be installed with commands such as:

`apt-get install php-curl php-xml`
`apt-get install php-soap`

... and whatever suits your needs.


### The module installation itself

This is the recommended way (and only officially supported) of installing the package.

* Get composer.
* Run composer:

`composer require tornevall/tornelib-php-netcurl`

## Documents

[Exceptions handling](https://docs.tornevall.net/x/EgCNAQ)


# NETCURL IS AND IS NOTS

[Read this document](https://docs.tornevall.net/x/GQCsAQ)


# HOWTOs

## Getting started

* [This document and furthermore information](https://docs.tornevall.net/x/CYBiAQ).
* [MODULE_CURL](https://docs.tornevall.net/x/EoBiAQ)


# Deprecations

## Auto detection of communicators

Using this call before running calls will try to prepare for a proper communications driver. If curl is available, the internal functions will be prioritized before others as this used to be best practice. However, if curl is missing, this might help you find a proper driver automatically.

    $LIB->setDriverAuto();
