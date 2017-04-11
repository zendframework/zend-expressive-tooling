<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\Module;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Zend\ComponentInstaller\Injector\InjectorInterface;
use Zend\Expressive\Tooling\Module\RegisterCommand;
use Zend\Expressive\Tooling\Module\Exception;
use ZF\ComposerAutoloading\Command\Enable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class RegisterCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;
    use MockeryPHPUnitIntegration;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $composer = 'my-composer';

    /** @var Register */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->dir = vfsStream::setup('project');
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);
        $this->command = new RegisterCommand('module:register');
        $this->expectedModuleArgumentDescription = RegisterCommand::HELP_ARG_MODULE;
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('Register a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(RegisterCommand::HELP, $this->command->getHelp());
    }

    public function injectedEnabled()
    {
        return [
            // $injected, $enabled
            [true,        true],
            [true,        false],
            [false,       true],
            [false,       false],
        ];
    }

    /**
     * @dataProvider injectedEnabled
     *
     * @param bool $injected
     * @param bool $enabled
     */
    public function testCommandEmitsExpectedMessagesWhenItInjectsConfigurationAndEnablesModule($injected, $enabled)
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(! $injected)
            ->once();
        if ($injected) {
            $injectorMock
                ->shouldReceive('inject')
                ->with('MyApp\ConfigProvider', InjectorInterface::TYPE_CONFIG_PROVIDER)
                ->once();
        } else {
            $injectorMock
                ->shouldNotReceive('inject');
        }

        $enableMock = Mockery::mock('overload:' . Enable::class);
        $enableMock
            ->shouldReceive('setMoveModuleClass')
            ->with(false)
            ->once();
        $enableMock
            ->shouldReceive('process')
            ->with('MyApp')
            ->andReturn($enabled)
            ->once();

        $this->input->getArgument('module')->willReturn('MyApp');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        $this->output
            ->writeln(Argument::containingString(
                'Registered autoloading rules and added configuration entry for module MyApp'
            ))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testEmitsExpectedErrorMessagesWhenRuntimeExceptionThrownFromEnable()
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(true)
            ->once();

        $enableMock = Mockery::mock('overload:' . Enable::class);
        $enableMock
            ->shouldReceive('setMoveModuleClass')
            ->with(false)
            ->once();
        $enableMock
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
