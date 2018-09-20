# NETCURL

[Full documents are located here](https://docs.tornevall.net/x/KwCy)


## Compatibility span (Supported PHP versions)

This library should be compatible with at least PHP 5.3 up to PHP 7.3 (RC2). However, running this module in older versions of PHP makes it more unreliable as PHP is continuosly developed. There are no guarantees that the module is fully functional from obsolete releases (like PHP 5.3 and most of the PHP 5.x-series is).

### Requirements and dependencies

Some kind of a supported driver is needed. NetCURL was built for curl so it could be a great idea to have curl available in your system. The goal with the module is however to pick up the best available driver in your system.

#### Supported drivers

* curl
* soap (SoapClient with XML)
* Guzzle
* Wordpress 

#### Future plans for independence

* Streams
* Sockets

### Dependencies and installation

As netcurl is built to be independently running dependencies is not necessesary required. To reach full functionality the list below might be good to have available.

* **Installation:** Composer. NetCURL can be bundled/manually downloaded, but the best practice is to install via composer. Otherwise you're a bit on your own.
* **SSL (OpenSSL):** Not having SSL available means that you won't be able to speak https
* **SOAP:** To make use of the SOAP components in NetCurl, XML libraries and SoapClient needs to be there. SoapClient uses Streams to fetch wsdl.

#### XML, CURL, SOAP

In apt-based systems, extra libraries can be installed with commands such as:

`apt-get install php-curl php-xml`


### The module installation itself

This is the recommended way (and only officially supported) of installing the package.

* Get composer.
* Run composer:

`composer require tornevall/tornelib-php-netcurl`

## Documents

[Exceptions handling](https://docs.tornevall.net/x/EgCNAQ)


## Auto detection of communicators

Using this call before running calls will try to prepare for a proper communications driver. If curl is available, the internal functions will be prioritized before others as this used to be best practice. However, if curl is missing, this might help you find a proper driver automatically.

    $LIB->setDriverAuto();


# NETCURL IS AND IS NOTS

[Read this document](https://docs.tornevall.net/x/GQCsAQ)


# HOWTOs

## Getting started

* [This document and furthermore information](https://docs.tornevall.net/x/CYBiAQ).
* [MODULE_CURL](https://docs.tornevall.net/x/EoBiAQ)
