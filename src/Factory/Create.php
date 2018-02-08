<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Factory;

use ReflectionClass;

class Create
{
    /**
     * @var FactoryClassGenerator
     */
    private $generator;

    public function __construct(FactoryClassGenerator $generator = null)
    {
        $this->generator = $generator ?: new FactoryClassGenerator();
    }

    /**
     * @return string Filename where factory was created
     * @throws FactoryAlreadyExistsException if a matching factory class file
     *     already exists in the filesystem
     * @throws ClassNotFoundException if the class cannot be autoloaded
     * @throws FactoryWriteException if unable to write the factory class file
     */
    public function createForClass(string $className) : string
    {
        if (! class_exists($className)) {
            throw ClassNotFoundException::forClassName($className);
        }

        $factoryFileName = sprintf(
            '%s/%sFactory.php',
            $this->getPathForClass($className),
            $this->getClassName($className)
        );

        if (file_exists($factoryFileName)) {
            throw FactoryAlreadyExistsException::forClassUsingFile($className, $factoryFileName);
        }

        if (! is_writable(dirname($factoryFileName))) {
            throw FactoryWriteException::whenCreatingFile($factoryFileName);
        }

        $factory = $this->generator->createFactory($className);

        if (false === file_put_contents($factoryFileName, $factory)) {
            throw FactoryWriteException::whenCreatingFile($factoryFileName);
        }

        return $factoryFileName;
    }

    private function getPathForClass(string $className) : string
    {
        $fileName = (new ReflectionClass($className))->getFileName();
        return dirname($fileName);
    }

    public function getClassName($class) : string
    {
        return substr($class, strrpos($class, '\\') + 1);
    }
}
