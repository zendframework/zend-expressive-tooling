# zend-expressive-tooling

[![Build Status](https://secure.travis-ci.org/zendframework/zend-expressive-tooling.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-expressive-tooling)
[![Coverage Status](https://coveralls.io/repos/github/zendframework/zend-expressive-tooling/badge.svg?branch=master)](https://coveralls.io/github/zendframework/zend-expressive-tooling?branch=master)

*Migration and development tools for Expressive.*

## Installation

Install via composer:

```bash
$ composer require --dev zendframework/zend-expressive-tooling
```

## `expressive` Tool

- `vendor/bin/expressive`: Entry point for all tooling. Currently exposes the
  following:

  - **action:create**: Create an action class file; this is an alias for the
    `handler:create` command, listed below.
  - **factory:create**: Create a factory class file for the named class. The
    class file is created in the same directory as the class specified.
  - **handler:create**: Create a PSR-15 request handler class file. Also
    generates a factory for the generated class, and, if a template renderer is
    registered with the application container, generates a template and modifies
    the class to render it into a zend-diactoros `HtmlResponse`.
  - **middleware:create**: Create a PSR-15 middleware class file.
  - **migrate:interop-middleware**: Migrate interop middlewares and delegators
    to PSR-15 middlewares and request handlers.
  - **migrate:middleware-to-request-handler**: Migrate PSR-15 middlewares to
    request handlers.
  - **module:create**: Create and register a middleware module with the
    application.
  - **module:deregister**: Deregister a middleware module from the application.
  - **module:register**: Register a middleware module with the application.

## Configurable command option values

If the `--modules-path` of your project is not under `src`, you can either
provide the path via the `--modules-path` command-line option, or configure it
within your application configuration. By adding the changed path to your
application configuration, you can omit the need to use the `--modules-path`
option during cli execution for the various `module:*` commands.

```php
// In config/autoload/application.global.php:

<?php

declare(strict_types = 1);

use Zend\Expressive\Tooling\Module\CommandCommonOptions;

return [
    /* ... */
    CommandCommonOptions::class => [
        '--modules-path' => 'custom-directory',
    ],
];
```
