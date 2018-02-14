<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\CreateHandler;

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
use Zend\Expressive\Tooling\CreateHandler\CreateHandler;
use Zend\Expressive\Tooling\CreateHandler\CreateHandlerCommand;
use Zend\Expressive\Tooling\CreateHandler\CreateHandlerException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateHandlerCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new CreateHandlerCommand('handler:create');
    }

    /**
     * Allows disabling of the `require` statement in the command class when testing.
     */
    private function disableRequireHandlerDirective(CreateHandlerCommand $command) : void
    {
        $r = new ReflectionProperty($command, 'requireHandlerBeforeGeneratingFactory');
        $r->setAccessible(true);
        $r->setValue($command, false);
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    /**
     * @return ObjectProphecy|Application
     */
    private function mockApplication(string $forService = 'Foo\TestHandler')
    {
        $helperSet = $this->prophesize(HelperSet::class)->reveal();

        $factoryCommand = $this->prophesize(Command::class);
        $factoryCommand
            ->run(
                Argument::that(function ($input) use ($forService) {
                    Assert::assertInstanceOf(ArrayInput::class, $input);
                    Assert::assertContains('factory:create', (string) $input);
                    Assert::assertContains($forService, (string) $input);
                    return $input;
                }),
                $this->output->reveal()
            )
            ->willReturn(0);

        $application = $this->prophesize(Application::class);
        $application->getHelperSet()->willReturn($helperSet);
        $application->find('factory:create')->will([$factoryCommand, 'reveal']);

        return $application;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains(CreateHandlerCommand::HELP_HANDLER_DESCRIPTION, $this->command->getDescription());
    }

    public function testConfigureSetsExpectedDescriptionWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create');
        $this->assertContains(CreateHandlerCommand::HELP_ACTION_DESCRIPTION, $command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedHelpWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create');
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION, $command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('handler'));
        $this->assertFalse($definition->hasArgument('action'));
        $argument = $definition->getArgument('handler');
        $this->assertTrue($argument->isRequired());
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER_ARG_HANDLER, $argument->getDescription());
    }

    public function testConfigureSetsExpectedArgumentsWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create');
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('action'));
        $this->assertFalse($definition->hasArgument('handler'));
        $argument = $definition->getArgument('action');
        $this->assertTrue($argument->isRequired());
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION_ARG_ACTION, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptions()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER_OPT_NO_FACTORY, $option->getDescription());

        $this->assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER_OPT_NO_REGISTER, $option->getDescription());
    }

    public function testConfigureSetsExpectedOptionsWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create');
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION_OPT_NO_FACTORY, $option->getDescription());

        $this->assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION_OPT_NO_REGISTER, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        $this->disableRequireHandlerDirective($this->command);
        $this->command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler')
            ->andReturn(__DIR__);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testSuccessfulExecutionEmitsExpectedMessagesWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create');
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication('Foo\TestAction')->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestAction')
            ->andReturn(__DIR__);

        $this->input->getArgument('action')->willReturn('Foo\TestAction');
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating action Foo\TestAction'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestAction, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateHandlerToBubbleUp()
    {
        $this->command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler')
            ->andThrow(CreateHandlerException::class, 'ERROR THROWN');

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
