<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Factory;

use ReflectionClass;
use ReflectionParameter;

class FactoryClassGenerator
{
    public const FACTORY_TEMPLATE = <<<'EOT'
<?php

declare(strict_types=1);

namespace %2$s;

%3$s

class %1$sFactory
{
    public function __invoke(ContainerInterface $container) : %1$s
    {
        return new %1$s(%4$s);
    }
}

EOT;

    public function createFactory(string $className) : string
    {
        $class = $this->getClassName($className);
        $namespace = $this->getNamespace($className);
        $constructorParameters = $this->getConstructorParameters($className);

        $imports = array_keys($constructorParameters);
        $imports[] = 'Psr\Container\ContainerInterface';

        return sprintf(
            self::FACTORY_TEMPLATE,
            $class,
            $namespace,
            $this->formatImportStatements($imports),
            $this->createArgumentString($constructorParameters)
        );
    }

    private function getClassName(string $className) : string
    {
        return substr($className, strrpos($className, '\\') + 1);
    }

    private function getNamespace(string $className) : string
    {
        return substr($className, 0, strrpos($className, '\\'));
    }

    /**
     * @throws UnidentifiedTypeException if a parameter defines a non-class typehint
     */
    private function getConstructorParameters(string $className) : array
    {
        $reflectionClass = new ReflectionClass($className);

        if (! $reflectionClass->getConstructor()) {
            return [];
        }

        $constructorParameters = $reflectionClass->getConstructor()->getParameters();

        if (! $constructorParameters) {
            return [];
        }

        $constructorParameters = array_filter(
            $constructorParameters,
            function (ReflectionParameter $argument) {
                if ($argument->isOptional()) {
                    return false;
                }

                if (null === $argument->getClass()) {
                    throw UnidentifiedTypeException::forArgument($argument->getName());
                }

                return true;
            }
        );

        if (! $constructorParameters) {
            return [];
        }

        $mappedParameters = [];
        foreach ($constructorParameters as $parameter) {
            $fqcn = $parameter->getClass()->getName();
            $mappedParameters[$fqcn] = $this->getClassName($fqcn);
        }

        return $mappedParameters;
    }

    private function formatImportStatements(array $imports) : string
    {
        natsort($imports);
        $imports = array_map(function ($import) {
            return sprintf('use %s;', $import);
        }, $imports);
        return implode("\n", $imports);
    }

    private function createArgumentString(array $arguments) : string
    {
        $arguments = array_map(function ($argument) {
            return sprintf('$container->get(%s::class)', $argument);
        }, $arguments);
        switch (count($arguments)) {
            case 0:
                return '';
            case 1:
                return array_shift($arguments);
            default:
                $argumentPad = str_repeat(' ', 12);
                $closePad = str_repeat(' ', 8);
                return sprintf(
                    "\n%s%s\n%s",
                    $argumentPad,
                    implode(",\n" . $argumentPad, $arguments),
                    $closePad
                );
        }
    }
}
