<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Factory;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\Factory\FactoryClassGenerator;
use ZendTest\Expressive\Tooling\Factory\TestAsset\ComplexDependencyObject;
use ZendTest\Expressive\Tooling\Factory\TestAsset\InvokableObject;
use ZendTest\Expressive\Tooling\Factory\TestAsset\SimpleDependencyObject;

class FactoryClassGeneratorTest extends TestCase
{
    /**
     * @var FactoryClassGenerator
     */
    private $generator;

    public function setUp()
    {
        $this->generator = new FactoryClassGenerator();
    }

    public function testCreateFactoryCreatesForInvokable()
    {
        $className = InvokableObject::class;
        $factory = file_get_contents(__DIR__ . '/TestAsset/factories/InvokableObject.php');

        $this->assertEquals($factory, $this->generator->createFactory($className));
    }

    public function testCreateFactoryCreatesForSimpleDependencies()
    {
        $className = SimpleDependencyObject::class;
        $factory = file_get_contents(__DIR__. '/TestAsset/factories/SimpleDependencyObject.php');

        $this->assertEquals($factory, $this->generator->createFactory($className));
    }

    public function testCreateFactoryCreatesForComplexDependencies()
    {
        $className = ComplexDependencyObject::class;
        $factory = file_get_contents(__DIR__. '/TestAsset/factories/ComplexDependencyObject.php');

        $this->assertEquals($factory, $this->generator->createFactory($className));
    }

    /**
     * @runTestInSeparateProcess
     */
    public function testCreateFactoryCreatesAppropriatelyNamedFactoryWhenClassNameAppearsWithinNamespace()
    {
        require __DIR__ . '/TestAsset/classes/ClassDuplicatingNamespaceName.php';
        $className = 'This\Duplicates\ClassDuplicatingNamespaceNameCase\ClassDuplicatingNamespaceName';
        $factory = file_get_contents(__DIR__ . '/TestAsset/factories/ClassDuplicatingNamespaceName.php');

        $this->assertEquals($factory, $this->generator->createFactory($className));
    }
}
