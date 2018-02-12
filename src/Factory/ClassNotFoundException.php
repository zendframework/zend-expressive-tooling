<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Factory;

use InvalidArgumentException;

class ClassNotFoundException extends InvalidArgumentException
{
    public static function forClassName(string $className) : self
    {
        return new self(sprintf(
            'Class "%s" could not be autoloaded; did you perhaps mis-type it?',
            $className
        ));
    }
}
