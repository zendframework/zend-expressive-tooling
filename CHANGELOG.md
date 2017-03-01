# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.3.0 - TBD

### Added

- [#12](https://github.com/zendframework/zend-expressive-tooling/pull/12) adds
  the new tool `expressive-module`, with the commands `create`, `register`, and
  `deregister`, for creating new "modules". `create` will create a tree under
  the `src/` tree named for the provided module containing `src/` and
  `templates/` subdirectories, as well as a `ConfigProvider` class; it then adds
  an entry for the `ConfigProvider` to the application configuration, and an
  autoloading entry to `composer.json`. `register` will register an existing
  module with the application configuration, and, if necessary, enable
  autoloading for it with `composer.json`. `deregister` does the opposite of
  `register`, without removing any files from the source tree. Use the command's
  `help`, `--help`, or `-h` options for full usage details.

### Changes

- [#10](https://github.com/zendframework/zend-expressive-tooling/pull/10)
  updates the `expressive-pipeline-from-config` tooling to no longer generate
  `pipeErrorHandler()` statements. It will now notify users via STDOUT if
  legacy error handlers are encountered, indicating which were encountered.

- [#10](https://github.com/zendframework/zend-expressive-tooling/pull/10)
  updates the `expressive-pipeline-from-config` tooling to now register the
  `DefaultDelegate` and `NotFoundDelegate` services, with the former aliased to
  the latter.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.0 - 2016-12-20

### Added

- Nothing.

### Changes

- [#7](https://github.com/zendframework/zend-expressive-tooling/pull/7) updates
  the `Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig\Generator`
  class such that it now:

  - Adds dependency configuration for `Zend\Expressive\Middleware\ImplicitHeadMiddleware`
  - Adds dependency configuration for `Zend\Expressive\Middleware\ImplicitOptionsMiddleware`
  - Registers each of the above middleware immediately following the
    routing middleware in the pipeline.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.3 - 2016-12-08

### Added

- Nothing.

### Changed

- [#6](https://github.com/zendframework/zend-expressive-tooling/pull/6) provides
  some internal refactoring of `Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig\Generator`
  to optimize performance and maintainability when generating the routing
  statements.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.2 - 2016-12-07

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#1](https://github.com/zendframework/zend-expressive-tooling/pull/1) updates
  the various `Help` classes to translate a command name to be relative to the
  `vendor/bin/` directory under every operating system when run local to a
  project.
- [#3](https://github.com/zendframework/zend-expressive-tooling/pull/3) fixes
  the top-level key used in generated configuration files to properly be
  `zf-expressive` instead of `zf-expressive-tooling`.
- [#5](https://github.com/zendframework/zend-expressive-tooling/pull/5) fixes
  the help message for the `expressive-pipeline-from-config` command to detail
  what it actually does (vs what the original incarnation did).

## 0.1.1 - 2016-12-06

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixed the namespace declarations of all vendor binaries to ensure each points
  to the correct tooling namespace for the command being invoked.

## 0.1.0 - 2016-12-06

- Initial release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
