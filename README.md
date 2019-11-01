# NETCURL 6.1

[Full documents are located here](https://docs.tornevall.net/x/KwCy)

## Note to self

Adding stuff to composer.

   "zendframework/zend-http": "^2.9"


## Compatibility span (Supported PHP versions)

This library is compatible with PHP releases from version 5.4 up to PHP 7.3 (because of the old grumpy developer coding syntax). Since the whole PHP 5 series are going obsolete you should however consider upgrading if not already done. It has also been built with NETCURL 6.0 in mind, where older engines may be replaced without any breaks.

### Requirements and dependencies

In its initial state, there are basically no requirements as this module tries to pick the best available driver when running. The prior version (6.0) has a basic CURL requirement (that's why it's called NETCURL) with some kind of unstated fallback to other drivers.

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
* Zend Framework

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


# Changes

Version 6.1 follows the standard of what's written in 6.0 - there is a primary module that handles all actions regardless of which driver installed. However, 6.0 is built on a curl-only-core with only halfway fixed PSR-supported code. For example, autoloading has a few workarounds to properly work with platforms that requires PSR-based code. In the prior release the class_exists-control rather generates a lot of warning rather than "doing it right". In 6.1 the order of behaviour is changed and all curl-only-based code has been restructured.

## Breaking changes?

As far as I see it, 6.1 is built to not break systems that runs on 6.0 except for possibly if someone is using PHP 5.3 which is highly obsolete as of a load of years back in time.
