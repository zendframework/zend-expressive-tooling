<?php

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Factory\TestAsset;

use Psr\Container\ContainerInterface;
use ZendTest\Expressive\Tooling\Factory\TestAsset\InvokableObject;

class SimpleDependencyObjectFactory
{
    public function __invoke(ContainerInterface $container) : SimpleDependencyObject
    {
        return new SimpleDependencyObject($container->get(InvokableObject::class));
    }
}
