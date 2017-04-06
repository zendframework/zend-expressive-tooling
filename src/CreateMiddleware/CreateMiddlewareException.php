<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\CreateMiddleware;

use RuntimeException;

class CreateMiddlewareException extends RuntimeException
{
    /**
     * @return self
     */
    public static function missingComposerJson()
    {
        return new self('Could not find a composer.json in the project root');
    }

    /**
     * @param string $error Error string related to JSON_ERROR_* constant
     * @return self
     */
    public static function invalidComposerJson($error)
    {
        return new self(sprintf(
            'Unable to parse composer.json: %s',
            $error
        ));
    }

    /**
     * @return self
     */
    public static function missingComposerAutoloaders()
    {
        return new self('composer.json does not define any PSR-4 autoloaders');
    }

    /**
     * @param string $class
     * @return self
     */
    public static function autoloaderNotFound($class)
    {
        return new self(sprintf(
            'Unable to match %s to an autoloadable PSR-4 namespace',
            $class
        ));
    }

    /**
     * @param string $path
     * @param string $class
     * @return self
     */
    public static function unableToCreatePath($path, $class)
    {
        return new self(sprintf(
            'Unable to create the directory %s for creating the class %s',
            $path,
            $class
        ));
    }
}
