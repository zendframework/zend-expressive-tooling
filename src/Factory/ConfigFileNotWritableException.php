<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Factory;

use RuntimeException;

class ConfigFileNotWritableException extends RuntimeException
{
    public static function forFile(string $file) : self
    {
        return new self(sprintf(
            'Cannot write factory configuration to file "%s";'
            . ' please make sure the file and directory are writable',
            $file
        ));
    }
}
