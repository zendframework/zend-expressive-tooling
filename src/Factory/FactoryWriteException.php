<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Factory;

use RuntimeException;

class FactoryWriteException extends RuntimeException
{
    public static function whenCreatingFile(string $filename) : self
    {
        return new self(sprintf(
            'Unable to create factory file "%s"; please verify you have write permissions to that directory',
            $filename
        ));
    }
}
