<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use RuntimeException;

class CreateHandlerException extends RuntimeException
{
    public static function missingComposerJson() : self
    {
        return new self('Could not find a composer.json in the project root');
    }

    /**
     * @param string $error Error string related to JSON_ERROR_* constant
     */
    public static function invalidComposerJson(string $error) : self
    {
        return new self(sprintf(
            'Unable to parse composer.json: %s',
            $error
        ));
    }

    public static function missingComposerAutoloaders() : self
    {
        return new self('composer.json does not define any PSR-4 autoloaders');
    }

    public static function autoloaderNotFound(string $class) : self
    {
        return new self(sprintf(
            'Unable to match %s to an autoloadable PSR-4 namespace',
            $class
        ));
    }

    public static function unableToCreatePath(string $path, string $class) : self
    {
        return new self(sprintf(
            'Unable to create the directory %s for creating the class %s',
            $path,
            $class
        ));
    }

    public static function classExists(string $path, string $class) : self
    {
        return new self(sprintf(
            'Class %s already exists in directory %s',
            $class,
            $path
        ));
    }
}
