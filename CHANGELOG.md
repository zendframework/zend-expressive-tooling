# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.0 - 2019-11-22

### Added

- [#96](https://github.com/zendframework/zend-expressive-tooling/pull/96) adds
  compatibility with symfony/console `^5.0`.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - 2019-08-28

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#93](https://github.com/zendframework/zend-expressive-tooling/pull/93) fixes
  issue with DI containers where configuration is an ArrayObject not an array.
  `handler:create` command works now properly with `Aura.Di` and `Symfony DI` containers.

## 1.2.0 - 2019-03-05

### Added

- [#85](https://github.com/zendframework/zend-expressive-tooling/pull/85) adds support for PHP 7.3.

- [#86](https://github.com/zendframework/zend-expressive-tooling/pull/86) adds the ability to provide the `--modules-path` option to the various
  `module:*` commands via configuration. In each case, if the option is omitted,
  the command will search for a `Zend\Expressive\Tooling\Module\CommandCommonOptions.--modules-path`
  configuration entry, and use it if present. When present, calling any of these
  commands can now omit the `--modules-path` option when a custom path is
  required.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.0 - 2019-01-22

### Added

- Nothing.

### Changed

- [#83](https://github.com/zendframework/zend-expressive-tooling/pull/83) fixes behavior when generating template names. The intention was for both
  namespace separators and TitleCase words to be dash-separated, but the latter
  previously were not; the patch in this release corrects the behavior. As such,
  names such as `DbExample` will now correctly map to a template with the name
  `db-example`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.2 - 2018-11-19

### Added

- Nothing.

### Changed

- [#80](https://github.com/zendframework/zend-expressive-tooling/pull/80) removes unnecessary `{@inheritDoc}` annotations from generated code.

- [#79](https://github.com/zendframework/zend-expressive-tooling/pull/79) adds `public` visibility to all declared constants. While this was assumed
  before, now it is explicit.

- [#81](https://github.com/zendframework/zend-expressive-tooling/pull/81) modifies the `action:create` and `handler:create` commands to emit a more
  sensible error in situations when the created `RequestHandlerInterface` is not
  namespaced.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.1 - 2018-03-27

### Added

- Nothing.

### Changed

- [#75](https://github.com/zendframework/zend-expressive-tooling/pull/75)
  modifies the generated `config/autoload/zend-expressive-tooling-factories.global.php`
  file to include a `strict_types` declaration, for consistency with other
  generated files.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#77](https://github.com/zendframework/zend-expressive-tooling/pull/77) fixes
  an issue with where generated template files were placed in the filesystem
  when working with a module; they are now correctly placed in the module's
  `templates` directory.

## 1.0.0 - 2018-03-15

### Added

- [#39](https://github.com/zendframework/zend-expressive-tooling/pull/39) and
  [#44](https://github.com/zendframework/zend-expressive-tooling/pull/44) add
  support for PSR-15. The `expressive middleware:create` command will now
  generate PSR-15 middleware.

- [#39](https://github.com/zendframework/zend-expressive-tooling/pull/39) and
  [#44](https://github.com/zendframework/zend-expressive-tooling/pull/44) add
  a new tool: `expressive migrate:interop-middleware`. This tool will migrate
  existing http-interop middleware, delegators, and/or request handlers of any
  version to PSR-15 middleware and request handlers.

- [#48](https://github.com/zendframework/zend-expressive-tooling/pull/48) adds a
  new command, `expressive handler:create`, which will create a PSR-15 request
  handler using the class name provided.

- [#52](https://github.com/zendframework/zend-expressive-tooling/pull/52) adds
  the command `factory:create`. The command expects a fully-qualified,
  resolvable, class name; it then generates a factory class for it as a sibling
  class file, using reflection. By default, it also registers the class and
  factory with the container, in the file `config/autoload/zend-expressive-tooling-factories.global.php`.
  Pass the option `--no-register` to disable this auto-registration.

- [#55](https://github.com/zendframework/zend-expressive-tooling/pull/55) adds
  an `action:create` command. This command is mapped to the existing
  `handler:create` command, which now varies its help messages and argument
  names based on the command name provided.

- [#58](https://github.com/zendframework/zend-expressive-tooling/pull/58) adds
  the command `migrate:middleware-to-request-handler`. This command accepts an
  optional `--src` option (default to `./src`), under which it will scan for
  class files where middleware is defined. If a given class file represents
  middleware, and the middleware does not call upon the handler argument, it
  rewrites the middleware as a request handler.

- [#63](https://github.com/zendframework/zend-expressive-tooling/pull/63) adds
  template generation capabilities to the `handler:create`/`action:create`
  commands. If a `TemplateRendererInterface` service is detected in the
  container, it will generate a template based on the root namespace of the
  generated class and the class name (minus any `Handler`, `Action`, or
  `Middleware` suffixes), and update the class to render the template into a
  zend-diactoros `HtmlResponse`. It also then exposes the following options:

  - `--without-template` disables template generation and template awareness in
    the generated class.

  - `--with-template-namespace` allows specifying an alternative template
    namespace.

  - `--with-template-name` allows specifying an alternative template
    name (separately from the namespace).

  - `--with-template-extension` allows specifying an alternative template
    file extension. By default, it will use the `templates.extension`
    configuration value, or a default based on known template renderers.

- Adds support for zend-component-installer `^2.0`.

### Changed

- [#52](https://github.com/zendframework/zend-expressive-tooling/pull/52)
  modifies the `middleware:create` command to invoke `factory:create` once it
  has successfully created the new middleware. You may disable this feature by
  passing the option `--no-factory`; if you want to generate the factory, but
  not auto-register the middleware service, pass the option `--no-register`.

- [#52](https://github.com/zendframework/zend-expressive-tooling/pull/52)
  modifies the `handler:create` command to invoke `factory:create` once it has
  successfully created the new request handler. You may disable this feature by
  passing the option `--no-factory`; if you want to generate the factory, but
  not auto-register the request handler service, pass the option
  `--no-register`.

- [#56](https://github.com/zendframework/zend-expressive-tooling/pull/56)
  modifies all generated classes to add a `declare(strict_types=1)` directive.

### Deprecated

- Nothing.

### Removed

- [#39](https://github.com/zendframework/zend-expressive-tooling/pull/39)
  removes support for http-interop/http-middleware.

- [#39](https://github.com/zendframework/zend-expressive-tooling/pull/39)
  removes support for PHP versions prior to PHP 7.1.

- Removes support for zend-component-installer `^1.1`.

- [#72](https://github.com/zendframework/zend-expressive-tooling/pull/72)
  removes the `migrate:expressive-v2.2` command; the 1.0.0 release explicitly
  requires zend-expressive 3, making that command useless.

- [#47](https://github.com/zendframework/zend-expressive-tooling/pull/47)
  removes a number of legacy commands built to help migration from Expressive
  version 1 to version 2, as they are no longer compatible with dependencies
  against with this version works. These commands include:

  - `expressive migrate:pipeline-from-config`
  - `expressive migrate:original-messages`
  - `expressive migrate:error-middleware-scanner`

- [#47](https://github.com/zendframework/zend-expressive-tooling/pull/47)
  removes all scripts other than `expressive` from the package definition.

### Fixed

- [#73](https://github.com/zendframework/zend-expressive-tooling/pull/73)
  reverts the change introduced by [#69](https://github.com/zendframework/zend-expressive-tooling/pull/69)
  as multi-segment namespaces are not yet supported by zf-component-installer,
  causing creation of the autoloader entry to result in an error during module
  creation.

- [#48](https://github.com/zendframework/zend-expressive-tooling/pull/48) fixes
  the description of the `expressive middleware:create` command to reference
  PSR-15 instead of http-interop.

- [#49](https://github.com/zendframework/zend-expressive-tooling/pull/49) fixes
  how the `module:create` command generates template configuration. It no longer
  produces "layout" and "error" configuration, and renames the "app"
  template namespace to a normalized version of the module name generated.

- [#69](https://github.com/zendframework/zend-expressive-tooling/pull/69) fixes
  an issue with `module:create` when presented with a multi-segment namespace.
  It now correctly creates a directory structure using all namespace segments.

## 0.4.7 - 2018-03-12

### Added

- [#71](https://github.com/zendframework/zend-expressive-tooling/pull/71) adds
  the new command `migrate:expressive-v2.2`. This command does the following:

  - Adds `Zend\Expressive\Router\ConfigProvider` to `config/config.php`.
  - Adds `Zend\Expressive\ConfigProvider` to `config/config.php`.
  - Replaces `pipeRoutingMiddleware()` calls with `pipe(\Zend\Expressive\Router\Middleware\RouteMiddleware::class)`.
  - Replaces `pipeDispatchMiddleware()` calls with `pipe(\Zend\Expressive\Router\Middleware\DispatchMiddleware::class)`.
  - Replaces `pipe()` calls that pipe `Implicit*Middleware` to reference zend-expressive-router variants.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.4.6 - 2018-01-29

### Added

- [#46](https://github.com/zendframework/zend-expressive-tooling/pull/46) adds
  compatibility with symfony/console `^4.0`.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.4.5 - 2017-12-11

### Added

- [#32](https://github.com/zendframework/zend-expressive-tooling/pull/32) adds a
  new argument to `CreateMiddleware::process()`, `$classSkeleton`; if provided,
  the value will be used as the skeleton for a new middleware class to generate,
  instead of the default provided with the tooling.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#31](https://github.com/zendframework/zend-expressive-tooling/pull/31)
  provides fixes to the various generators such that they will now throw
  exceptions if the middleware they are attempting to create already exist on
  the filesystem.

## 0.4.4 - 2017-05-09

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#28](https://github.com/zendframework/zend-expressive-tooling/pull/28) adds
  to the bootstrap of the the `expressive` command another path to check for the
  autoloader; this new path is necessary to enable autoloading to work correctly
  on MacOS.

## 0.4.3 - 2017-04-28

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#24](https://github.com/zendframework/zend-expressive-tooling/pull/24) fixes
  incorrect use statement in `CreateMiddleware::CLASS_SKELETON`.

- [#25](https://github.com/zendframework/zend-expressive-tooling/pull/25) fixes
  symfony/console required version to be less restrictive.

## 0.4.2 - 2017-04-26

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#26](https://github.com/zendframework/zend-expressive-tooling/pull/26)
  updates the constraints for:
  - zend-expressive to `^2.0` only, since that has now been released.
  - zend-component-installer to `^1.0 || ^0.7.1`, fixing an issue when
    installing Expressive with modular support due to constraint violations.

## 0.4.1 - 2017-04-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updates zend-component-installer minimum version to 0.7.1, which provides a
  fix for detection of config providers; prior to this fix, `module:degister`
  could not remove globally qualified config providers for a module.

## 0.4.0 - 2017-04-11

### Added

- [#22](https://github.com/zendframework/zend-expressive-tooling/pull/22) and
  [#23](https://github.com/zendframework/zend-expressive-tooling/pull/23) add
  the script `expressive`, which allows executing any of the other commands
  provided in the package, including a new command for middleware creation. The
  exposed commands are:

  - **middleware:create**: Create an http-interop middleware class file.
  - **migrate:error-middleware-scanner**: Scan for legacy error middleware or error middleware invocation.
  - **migrate:original-messages**: Migrate getOriginal*() calls to request attributes.
  - **migrate:pipeline**: Generate a programmatic pipeline and routes from configuration.
  - **module:create**: Create and register a middleware module with the application
  - **module:deregister**: Deregister a middleware module from the application
  - **module:register**: Register a middleware module with the application

  All previous scripts (e.g., `expressive-pipeline-from-config`) are still
  present and continue to work, but are deprecated in favor of the `expressive`
  script.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.3.2 - 2017-03-13

### Added

- Nothing.

### Changed

- [#17](https://github.com/zendframework/zend-expressive-tooling/pull/17)
  changes the reference to the `DefaultDelegate` in the generated
  `config/autoload/programmatic-pipeline.global.php` to be a string instead of
  using `::class` notation. Using a string name makes it clear the service is
  not a concrete class or interface name.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#16](https://github.com/zendframework/zend-expressive-tooling/pull/16) fixes
  generation of routes where no HTTP method is specified to use a `null` instead
  of the `Zend\Expressive\Router\Route::HTTP_METHOD_ANY` constant.

## 0.3.1 - 2017-03-02

### Added

- [#15](https://github.com/zendframework/zend-expressive-tooling/pull/15) adds
  documentation for the `expressive-module` command to the README file.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#14](https://github.com/zendframework/zend-expressive-tooling/pull/14) fixes
  the `public/index.php` template to remove the `error_reporting()` declaration,
  as it is no longer necessary with Stratigily 2 and the upcoming Expressive 2
  release.

## 0.3.0 - 2017-03-01

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
