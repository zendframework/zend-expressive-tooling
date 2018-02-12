<?php

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Factory\TestAsset;

use Psr\Container\ContainerInterface;
use ZendTest\Expressive\Tooling\Factory\TestAsset\SecondComplexDependencyObject;
use ZendTest\Expressive\Tooling\Factory\TestAsset\SimpleDependencyObject;

class ComplexDependencyObjectFactory
{
    public function __invoke(ContainerInterface $container) : ComplexDependencyObject
    {
        return new ComplexDependencyObject(
            $container->get(SimpleDependencyObject::class),
            $container->get(SecondComplexDependencyObject::class)
        );
    }
}
