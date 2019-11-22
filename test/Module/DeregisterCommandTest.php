<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Module;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Zend\Expressive\Tooling\Module\DeregisterCommand;
use ZF\ComposerAutoloading\Command\Disable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class DeregisterCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;
    use MockeryPHPUnitIntegration;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
    private $output;

    /** @var DeregisterCommand */
    private $command;

    /** @var string */
    private $expectedModuleArgumentDescription;

    protected function setUp() : void
    {
        parent::setUp();

        $this->dir = vfsStream::setup('project');
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);
        $this->command = new DeregisterCommand('module:deregister');
        $this->expectedModuleArgumentDescription = DeregisterCommand::HELP_ARG_MODULE;
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertStringContainsString('Deregister a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(DeregisterCommand::HELP, $this->command->getHelp());
    }

    public function removedDisabled()
    {
        return [
            // $removed, $disabled
            [true,       true],
            [true,       false],
            [false,      true],
            [false,      false],
        ];
    }

    /**
     * @dataProvider removedDisabled
     *
     * @param bool $removed
     * @param bool $disabled
     */
    public function testRemoveFromConfigurationAndDisableModuleEmitsExpectedMessages($removed, $disabled)
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn($removed)
            ->once();
        if ($removed) {
            $injectorMock
                ->shouldReceive('remove')
                ->with('MyApp\ConfigProvider')
                ->once();
        } else {
            $injectorMock
                ->shouldNotReceive('remove');
        }

        $disableMock = Mockery::mock('overload:' . Disable::class);
        $disableMock
            ->shouldReceive('process')
            ->with('MyApp')
            ->andReturn($disabled)
            ->once();

        $this->input->getArgument('module')->willReturn('MyApp');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        $this->output
            ->writeln(Argument::containingString(
                'Removed autoloading rules and configuration entries for module MyApp'
            ))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsThrownFromDisableToBubbleUp()
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(false)
            ->once();

        $disableMock = Mockery::mock('overload:' . Disable::class);
        $disableMock
            ->shouldReceive('process')
            ->with('MyApp')
            ->andThrow(RuntimeException::class, 'Testing Exception Message')
            ->once();

        $this->input->getArgument('module')->willReturn('MyApp');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        $method = $this->reflectExecuteMethod();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Testing Exception Message');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
