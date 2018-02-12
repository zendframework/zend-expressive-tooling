<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\CreateMiddleware;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Zend\Expressive\Tooling\CreateMiddleware\CreateMiddleware;
use Zend\Expressive\Tooling\CreateMiddleware\CreateMiddlewareCommand;
use Zend\Expressive\Tooling\CreateMiddleware\CreateMiddlewareException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateMiddlewareCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new CreateMiddlewareCommand('middleware:create');

        // Do not require the generated middleware during testing
        $r = new ReflectionProperty($this->command, 'requireMiddlewareBeforeGeneratingFactory');
        $r->setAccessible(true);
        $r->setValue($this->command, false);
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    private function mockApplication()
    {
        $helperSet = $this->prophesize(HelperSet::class)->reveal();

        $factoryCommand = $this->prophesize(Command::class);
        $factoryCommand
            ->run(
                Argument::that(function ($input) {
                    Assert::assertInstanceOf(ArrayInput::class, $input);
                    Assert::assertContains('factory:create', (string) $input);
                    Assert::assertContains('Foo\TestMiddleware', (string) $input);
                    return $input;
                }),
                $this->output->reveal()
            )
            ->willReturn(0);

        $application = $this->prophesize(Application::class);
        $application->getHelperSet()->willReturn($helperSet);
        $application->find('factory:create')->will([$factoryCommand, 'reveal']);

        return $application;

        $this->command->setApplication($application->reveal());
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('Create a PSR-15 middleware', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(CreateMiddlewareCommand::HELP, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('middleware'));
        $argument = $definition->getArgument('middleware');
        $this->assertTrue($argument->isRequired());
        $this->assertEquals(CreateMiddlewareCommand::HELP_ARG_MIDDLEWARE, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptions()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateMiddlewareCommand::HELP_OPT_NO_FACTORY, $option->getDescription());

        $this->assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateMiddlewareCommand::HELP_OPT_NO_REGISTER, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        $this->command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateMiddleware::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestMiddleware')
            ->andReturn(__DIR__);

        $this->input->getArgument('middleware')->willReturn('Foo\TestMiddleware');
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating middleware Foo\TestMiddleware'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestMiddleware, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateMiddlewareToBubbleUp()
    {
        $this->command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateMiddleware::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestMiddleware')
            ->andThrow(CreateMiddlewareException::class, 'ERROR THROWN');

        $this->input->getArgument('middleware')->willReturn('Foo\TestMiddleware');
        $this->output
            ->writeln(Argument::containingString('Creating middleware Foo\TestMiddleware'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
