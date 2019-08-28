<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling;

use ArrayObject;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;
use Zend\Expressive\Tooling\ConfigAndContainerTrait;

class ConfigAndContainerTraitTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $projectRoot;

    protected function setUp() : void
    {
        $this->dir = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
    }

    public function testGetContainer() : void
    {
        $class = new class ()
        {
            use ConfigAndContainerTrait;

            public function container(string $projectPath)
            {
                return $this->getContainer($projectPath);
            }
        };

        self::assertInstanceOf(ContainerInterface::class, $class->container($this->projectRoot));
    }

    public function testGetConfigAsArray() : void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(['foo' => 'bar']);

        $class = new class ($container->reveal())
        {
            use ConfigAndContainerTrait;

            public function __construct(ContainerInterface $container)
            {
                $this->container = $container;
            }

            public function config()
            {
                return $this->getConfig('');
            }
        };

        self::assertSame(['foo' => 'bar'], $class->config());
    }

    public function testGetConfigAsArrayObject() : void
    {
        $config = new ArrayObject(['bar' => 'baz']);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);

        $class = new class ($container->reveal())
        {
            use ConfigAndContainerTrait;

            public function __construct(ContainerInterface $container)
            {
                $this->container = $container;
            }

            public function config()
            {
                return $this->getConfig('');
            }
        };

        self::assertSame(['bar' => 'baz'], $class->config());
    }

    public function testConfigHasInvalidType() : void
    {
        $config = new stdClass();
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);

        $class = new class ($container->reveal())
        {
            use ConfigAndContainerTrait;

            public function __construct(ContainerInterface $container)
            {
                $this->container = $container;
            }

            public function config()
            {
                return $this->getConfig('');
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"config" service must be an array or instance of ArrayObject');
        $class->config();
    }
}
