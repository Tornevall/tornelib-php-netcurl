# NETCURL 6.1

Full documents for v6.0 is located [here](https://docs.tornevall.net/x/KwCy).
For v6.1 -- please hold.


## Compatibility span (Supported PHP versions)

This library is built to work with PHP 5.6 and higher (I prefer to follow the live updates of PHP with their EOL's - [check it here](https://www.php.net/supported-versions.php)). The [Bamboo-instance](https://bamboo.tornevall.net) used is built in the past, so I'm still able to test the library with older PHP releases too. Even if I think they will fail any time now.

However, it is not that easy. The compatibility span **has** to be lower as the world I'm living in tend to be slow. If this module is built after the bleeding edge-principles, that also means that something will blow up somewhere. It's disussable whether that's something to ignore or not, but I think it's important to be supportive regardless of end of life-restrictions (but not too far). When support ends from software developers point of view, I see a perfect moment to follow that stream. This is very important as 2019 and 2020 seems to be two such years when most of the society is forcing movement forward. 

To keep compatibility with v6.0 the plan is to keep the primary class MODULE_CURL callable from a root position. It will probably be recommended to switch over to a PSR friendly structure from there, but the base will remain in 6.1 and the best way to instantiate the module in future is to call for the same wrapper as the main MODULE_CURL will use - NetWrapper (TorneLIB\Module\Network\NetWrapper) as it is planned to be the primary driver handler.

### Requirements and dependencies

In its initial state, there are basically no requirements as this module tries to pick the best available driver in runtime.

### What NETCURL support

... or even better, **should** support.

* Curl
* Guzzle
* RSS feeds
* SoapClient (dependencies)
* Sockets
* Streams
* Zend

#### Further dependencies on special support

* SSL: OpenSSL or similar.
* SOAP: SoapClient and XML-drivers.

### Installation

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
* Run composer.

      composer require tornevall/tornelib-php-netcurl
      
Or more preferrably either...

      composer require tornevall/tornelib-php-netcurl ^6.1 

or during development...
      
      composer require tornevall/tornelib-php-netcurl dev-develop/6.1 

## Documents

* [Version 6.1](https://docs.tornevall.net/display/TORNEVALL/NETCURLv6.1)
* [Exceptions handling for v6.0](https://docs.tornevall.net/x/EgCNAQ)


# NETCURL IS AND IS NOTS

* [Written for 6.0](https://docs.tornevall.net/x/GQCsAQ)


# HOWTOs

## Getting started

* [MODULE_CURL 6.1 - coming soon](https://docs.tornevall.net/display/TORNEVALL/NETCURLv6.1)
* [MODULE_CURL 6.0](https://docs.tornevall.net/x/EoBiAQ)


# Changes

Version 6.1 follows the standard of what's written in 6.0 - there is a primary module that handles all actions regardless of which driver installed. However, 6.0 is built on a curl-only-core with only halfway fixed PSR-supported code. For example, autoloading has a few workarounds to properly work with platforms that requires PSR-based code. In the prior release the class_exists-control rather generates a lot of warning rather than "doing it right". In 6.1 the order of behaviour is changed and all curl-only-based code has been restructured.

## Breaking changes?

No. Version 6.1 is written to reach highest compatibility with v6.1 as possible.


# Composer addons

        "phpunit/phpunit": "^7.5",
        "zendframework/zend-http": "^2.11",
        "guzzlehttp/guzzle": "^6.5"
        "zendframework/zend-http": "^2.9"

