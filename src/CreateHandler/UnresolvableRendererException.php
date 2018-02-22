<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use RuntimeException;
use Zend\Expressive\Template\TemplateRendererInterface;

class UnresolvableRendererException extends RuntimeException
{
    public static function dueToMissingAlias() : self
    {
        return new self(sprintf(
            'Unable to determine what type of template renderer is in use due'
            . ' to an inability to detect a service alias for the service %s;'
            . ' cannot create template.',
            TemplateRendererInterface::class
        ));
    }

    public static function dueToUnknownType(string $type) : self
    {
        return new self(sprintf(
            'Detected an unknown template renderer type "%s", and thus cannot'
            . ' create a template as we do not know what extension to use.',
            $type
        ));
    }
}
