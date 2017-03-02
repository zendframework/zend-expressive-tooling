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

- `vendor/bin/expressive-migrate-original-messages`: Ensure your application
  does not use the Stratigility-specific PSR-7 message decorators.

- `vendor/bin/expressive-module`: Create source tree for the expressive
  module, de/register module in application configuration, and enable/disable
  autoloading of module via composer.

- `vendor/bin/expressive-pipeline-from-config`: Update a pre-1.1 Expressive
  application to use programmatic pipelines instead.

- `vendor/bin/expressive-scan-for-error-middleware`: Scan for Stratigility
  `ErrorMiddlewareInterface` implementations (both direct and duck-typed), as
  well as invocations of error middleware (via the optional third argument to
  `$next`).

Each will provide usage details when invoked without arguments, or with the
`help`, `--help`, or `-h` flags.
