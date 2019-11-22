<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Module;

use ArrayObject;
use Generator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Zend\Expressive\Tooling\Module\Create;
use Zend\Expressive\Tooling\Module\CreateCommand;
use Zend\Expressive\Tooling\Module\RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;
    use MockeryPHPUnitIntegration;

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
    private $output;

    /** @var CreateCommand */
    private $command;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $projectRoot;

    /** @var string */
    private $expectedModuleArgumentDescription;

    protected function setUp() : void
    {
        $this->dir = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');

        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new CreateCommand('module:create');
        $this->expectedModuleArgumentDescription = CreateCommand::HELP_ARG_MODULE;

        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function injectConfigInContainer(bool $configAsArrayObject = false)
    {
        $configFile = $this->projectRoot . '/config/config.php';
        $config = include $configFile;

        if ($configAsArrayObject) {
            $config = new ArrayObject($config);
        }

        $this->container->get('config')->willReturn($config);
    }

    public function configType() : Generator
    {
        yield 'array'       => [false];
        yield 'ArrayObject' => [true];
    }

    public function injectContainerInCommand()
    {
        $r = new ReflectionProperty($this->command, 'container');
        $r->setAccessible(true);
        $r->setValue($this->command, $this->container->reveal());
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    private function mockApplicationWithRegisterCommand($return, $name, $module, $composer, $modulesPath, $output)
    {
        $register = $this->prophesize(Command::class);
        $register
            ->run(
                Argument::that(function ($input) use ($name, $module, $composer, $modulesPath) {
                    TestCase::assertInstanceOf(ArrayInput::class, $input);

                    $r = new ReflectionProperty($input, 'parameters');
                    $r->setAccessible(true);
                    $parameters = $r->getValue($input);

                    TestCase::assertArrayHasKey('command', $parameters);
                    TestCase::assertEquals($name, $parameters['command']);

                    TestCase::assertArrayHasKey('module', $parameters);
                    TestCase::assertEquals($module, $parameters['module']);

                    TestCase::assertArrayHasKey('--composer', $parameters);
                    TestCase::assertEquals($composer, $parameters['--composer']);

                    TestCase::assertArrayHasKey('--modules-path', $parameters);
                    TestCase::assertEquals($modulesPath, $parameters['--modules-path']);

                    return true;
                }),
                $output
            )
            ->willReturn($return);

        // HelperSet is needed as setApplication retrieves it to inject in the new command
        $helperSet = $this->prophesize(HelperSet::class);

        $application = $this->prophesize(Application::class);
        $application->find($name)->will([$register, 'reveal']);
        $application->getHelperSet()->will([$helperSet, 'reveal']);
        return $application;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertStringContainsString('Create and register a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(CreateCommand::HELP, $this->command->getHelp());
    }

    /**
     * @dataProvider configType
     */
    public function testCommandEmitsExpectedSuccessMessages(bool $configAsArrayObject)
    {
        $creation = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', getcwd())
            ->andReturn('SUCCESSFULLY RAN CREATE');

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $this->injectConfigInContainer($configAsArrayObject);
        $this->injectContainerInCommand();

        $this->output->writeln(Argument::containingString('SUCCESSFULLY RAN CREATE'))->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            0,
            'module:register',
            'Foo',
            'composer.phar',
            'library/modules',
            $this->output->reveal()
        );
        $this->command->setApplication($app->reveal());

        $method = $this->reflectExecuteMethod();
        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @dataProvider configType
     */
    public function testCommandWillFailIfRegisterFails(bool $configAsArrayObject)
    {
        $creation = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', getcwd())
            ->andReturn('SUCCESSFULLY RAN CREATE');

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $this->injectConfigInContainer($configAsArrayObject);
        $this->injectContainerInCommand();

        $this->output->writeln(Argument::containingString('SUCCESSFULLY RAN CREATE'))->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            1,
            'module:register',
            'Foo',
            'composer.phar',
            'library/modules',
            $this->output->reveal()
        );
        $this->command->setApplication($app->reveal());

        $method = $this->reflectExecuteMethod();
        $this->assertSame(1, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @dataProvider configType
     */
    public function testCommandAllowsExceptionsToBubbleUp(bool $configAsArrayObject)
    {
        $creation = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->with('Foo', 'library/modules', getcwd())
            ->once()
            ->andThrow(RuntimeException::class, 'ERROR THROWN');

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $this->injectConfigInContainer($configAsArrayObject);
        $this->injectContainerInCommand();

        $method = $this->reflectExecuteMethod();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ERROR THROWN');
        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
