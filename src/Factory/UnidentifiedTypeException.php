<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Factory;

use RuntimeException;

class UnidentifiedTypeException extends RuntimeException
{
    public static function forArgument(string $argument) : self
    {
        return new self(sprintf(
            'Cannot identify type for constructor argument "%s"; '
            . 'no type hint, or non-class/interface type hint',
            $argument
        ));
    }
}
