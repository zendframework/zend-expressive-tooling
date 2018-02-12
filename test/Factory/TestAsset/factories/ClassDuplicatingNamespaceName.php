<?php

declare(strict_types=1);

namespace This\Duplicates\ClassDuplicatingNamespaceNameCase;

use Psr\Container\ContainerInterface;

class ClassDuplicatingNamespaceNameFactory
{
    public function __invoke(ContainerInterface $container) : ClassDuplicatingNamespaceName
    {
        return new ClassDuplicatingNamespaceName();
    }
}
