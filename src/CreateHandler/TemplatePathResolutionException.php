<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use RuntimeException;

class TemplatePathResolutionException extends RuntimeException
{
    public static function forNamespace(string $namespace) : self
    {
        return new self(sprintf(
            'Template path configuration for the namespace "%s" either'
            . ' had no entries or more than one entry; could not determine'
            . ' where to create new template.',
            $namespace
        ));
    }
}
