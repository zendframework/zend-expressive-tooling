<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use RuntimeException;

class UndetectableNamespaceException extends RuntimeException
{
    public static function forPath(string $path) : self
    {
        return new self(sprintf(
            'Unable to determine namespace from class file path "%s";'
            . ' are you using a non-standard source tree layout?',
            $path
        ));
    }
}
