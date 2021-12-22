![rawpixel - Unsplash (UL) #SEDqvdbkDQw](https://images.unsplash.com/photo-1614267119077-51bdcbf9f77a?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Stable Version](https://poser.pugx.org/darkghosthunter/transbank/v/stable)](https://packagist.org/packages/darkghosthunter/transbank) [![License](https://poser.pugx.org/darkghosthunter/transbank/license)](https://packagist.org/packages/darkghosthunter/transbank) ![](https://img.shields.io/packagist/php-v/darkghosthunter/transbank.svg) [![PHP Composer](https://github.com/DarkGhostHunter/TransbankApi/workflows/PHP%20Composer/badge.svg)](https://github.com/DarkGhostHunter/transbank/actions) [![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Transbank/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Transbank?branch=master)

# Transbank

Easy-to-use Transbank SDK for PHP.

Supports Webpay, Webpay Mall and Webpay Oneclick Mall.

## Requisites:

* PHP 8.0 or later.
* `ext-json`
* [HTTP Client](#http-client)
* [Logger](#logger-optional) (optional)
* [Event dispatcher](#event-dispatcher-optional) (optional)

# Installation

Require it with [Composer](https://getcomposer.org/):

    composer require darkghosthunter/webpay

## Usage

This SDK mimics all the Webpay, Webpay Mall and Oneclick Mall methods from the official Transbank SDK for PHP.

You can check the documentation of these services in Transbank Developer's site.

- [Webpay](https://www.transbankdevelopers.cl/documentacion/webpay-plus#webpay-plus)
- [Webpay Mall](https://www.transbankdevelopers.cl/documentacion/webpay-plus#webpay-plus-mall)
- [Oneclick Mall](https://www.transbankdevelopers.cl/documentacion/oneclick)

## Quickstart

Instance the `Transbank` object. You can do it manually, or with `make()` which will use Guzzle o Symfony HTTP Clients if any of these is already installed.

```php
use DarkGhostHunter\Transbank\Transbank;

$transbank = Transbank::make();
```

If your project doesn't manage singletons, you can use `singletonBuilder()` to save a builder function, and recall the singleton with `singleton()`.

```php
use DarkGhostHunter\Transbank\Transbank;

Transbank::singletonBuilder(function () : Transbank {
    return Transbank::make()->toProduction([
        'webpay' => ['key' => '...', 'secret' => '...']
    ]);
});

$tbk = Transbank::singleton();
```

### Environments and credentials

By default, this SDK starts up in **integration** environment, where all transactions made are fake by using Transbank's own _integration_ server.

To operate in production mode, where all transaction will be real, you will need to use `toProduction()` along an `array` with the name of the service and their production credentials issued by Transbank to you: `webpay`, `webpayMall` or `oneclickMall`.

```php
$transbank->toProduction([
    'patpass' => ['555876543210','7a7b7d6cce5e...']
]);
```

> For Mall operations, the "child" commerce code is only needed when doing the transactions.

### Using a Service

To use a Transbank service, just call the method on the `Transbank` instance: `webpay`, `webpayMall` and `oneclickMall`.

```php
use DarkGhostHunter\Transbank\Transbank;

$transaction = Transbank::singleton()
        ->webpay()
        ->create('order#123', 9990, 'https://app.com/compra');
```

### HTTP Client

This package is compatible with any PSR-18 compliant HTTP Client. IF you don't have one, you can install [Guzzle](https://docs.guzzlephp.org/) or [Symfony](https://symfony.com/doc/current/http_client.html)

    composer require guzzlehttp/guzzle:>=7.0

or

    composer require symfony/http-client:>=5.2

Some PHP platforms already ship with their own HTTP Client, like [Amp](https://amphp.org/http-client/), [ReactPHP](https://reactphp.org/http/), or [Swoole](https://www.swoole.co.uk/docs/modules/swoole-coroutine-http-client).

### Logger (optional)

You can use this package with any PSR-3 compliant Log system, allowing to debug transactions. You can use [Monolog](https://github.com/Seldaek/monolog) if you don't have one.

    composer require monolog/monolog

All operations are sent to the logger using `debug`.

### Event dispatcher (optional)

You can use this package with any PSR-14 compliant Event Dispatcher, so you will be able to hear transactions started and completed. You can use [Symfony](https://github.com/symfony/event-dispatcher) or [League](https://event.thephpleague.com/) if you don't have one.

    composer require symfony/event-dispatcher

or

    composer require league/event

This package sends the following events:

* `TransactionCreating` before a transaction is created in Transbank.
* `TransactionCreated` after a transaction is created in Transbank, but pending payment.
* `TransactionCompleted` after a transaction or refund is completed in Transbank, regardless of the success.

### Excepciones

All exceptions implement `TransbankException`, so you can easily catch and check what happened.

> Transactions properly rejected by banks or credit card issuers do not throw exceptions.

There are 4 types of exceptions:

* `ClientException`: Any error byproduct of bad transactions, bad configuration, abort, abandonment, timeout or invalid values.
* `NetworkException`: Any communication error from Transbank Server, like network timeouts or wrong endpoints.
* `ServerException`: Any internal Transbank error.
* `UnknownException`: Any other error.

> Exceptions are **not** logged.

## [Run examples locally](examples/README.md)

# Licence

The MIT License (MIT). Please see [License File](LICENSE) for more information.

`Redcompra`, `Webpay`, `Oneclick`, `Onepay`, `Patpass` and `Transbank` are trademarks of [Transbank S.A.](https://www.transbank.cl/). This package and its author are not associated with Transbank S.A.
