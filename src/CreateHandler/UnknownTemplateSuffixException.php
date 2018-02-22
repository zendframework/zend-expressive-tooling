<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use RuntimeException;

class UnknownTemplateSuffixException extends RuntimeException
{
    public static function forRendererType(string $type) : self
    {
        return new self(sprintf(
            'Could not determine template file extension for renderer of type %s;'
            . ' please set the templates.extension configuration option, or pass'
            . ' the extension to use via the --with-template-extension option.',
            $type
        ));
    }
}
