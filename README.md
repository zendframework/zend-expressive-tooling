# zend-expressive-tooling

[![Build Status](https://secure.travis-ci.org/zendframework/zend-expressive-tooling.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-expressive-tooling)
[![Coverage Status](https://coveralls.io/repos/github/zendframework/zend-expressive-tooling/badge.svg?branch=master)](https://coveralls.io/github/zendframework/zend-expressive-tooling?branch=master)

*Migration and development tools for Expressive.*

## Installation

Install via composer:

```bash
$ composer require --dev zendframework/zend-expressive-tooling
```

## Tools

- `vendor/bin/expressive`: Meta-command for invoking all other commands. When
  using this command, you can call any of the other commands minus the
  `expressive-` prefix: e.g., `expressive module create Foo`. This command also
  supports `help` operations of either the form `expressive help <command>` or
  `expressive <command> help`.

- `vendor/bin/expressive-create-middleware`: Create an http-interop middleware
  class name; the class file is created based on matching a PSR-4 autoloader
  defined in your `composer.json`.

- `vendor/bin/expressive-migrate-original-messages`: Ensure your application
  does not use the Stratigility-specific PSR-7 message decorators.

- `vendor/bin/expressive-module`: Create the source tree for an Expressive
  module, de/register the module in application configuration, and
  enable/disable autoloading of the module via composer.

- `vendor/bin/expressive-pipeline-from-config`: Update a pre-2.0 Expressive
  application to use programmatic pipelines instead.

- `vendor/bin/expressive-scan-for-error-middleware`: Scan for Stratigility
  `ErrorMiddlewareInterface` implementations (both direct and duck-typed), as
  well as invocations of error middleware (via the optional third argument to
  `$next`).

Each will provide usage details when invoked without arguments, or with the
`help`, `--help`, or `-h` flags.
