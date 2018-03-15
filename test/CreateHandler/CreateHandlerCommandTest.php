<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\CreateHandler;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
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
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Tooling\CreateHandler\CreateHandler;
use Zend\Expressive\Tooling\CreateHandler\CreateHandlerCommand;
use Zend\Expressive\Tooling\CreateHandler\CreateHandlerException;
use Zend\Expressive\Tooling\CreateHandler\CreateTemplate;
use Zend\Expressive\Tooling\CreateHandler\Template;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateHandlerCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
    private $output;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->container = $this->prophesize(ContainerInterface::class);
    }

    private function createCommand(string $command = 'handler:create') : CreateHandlerCommand
    {
        return new CreateHandlerCommand(
            'handler:create',
            null,
            $this->container->reveal()
        );
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

    private function reflectExecuteMethod(CreateHandlerCommand $command) : ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
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

    public function testConfigureSetsExpectedDescriptionWhenRequestingAHandler()
    {
        $command = $this->createCommand();
        $this->assertContains(CreateHandlerCommand::HELP_HANDLER_DESCRIPTION, $command->getDescription());
    }

    public function testConfigureSetsExpectedDescriptionWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create', null, $this->container->reveal());
        $this->assertContains(CreateHandlerCommand::HELP_ACTION_DESCRIPTION, $command->getDescription());
    }

    public function testConfigureSetsExpectedHelpWhenRequestingAHandler()
    {
        $command = $this->createCommand();
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER, $command->getHelp());
    }

    public function testConfigureSetsExpectedHelpWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create', null, $this->container->reveal());
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION, $command->getHelp());
    }

    public function testConfigureSetsExpectedArgumentsWhenRequestingAHandler()
    {
        $command = $this->createCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('handler'));
        $this->assertFalse($definition->hasArgument('action'));
        $argument = $definition->getArgument('handler');
        $this->assertTrue($argument->isRequired());
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER_ARG_HANDLER, $argument->getDescription());
    }

    public function testConfigureSetsExpectedArgumentsWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create', null, $this->container->reveal());
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('action'));
        $this->assertFalse($definition->hasArgument('handler'));
        $argument = $definition->getArgument('action');
        $this->assertTrue($argument->isRequired());
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION_ARG_ACTION, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptionsWhenRequestingAHandler()
    {
        $command = $this->createCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER_OPT_NO_FACTORY, $option->getDescription());

        $this->assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_HANDLER_OPT_NO_REGISTER, $option->getDescription());

        $this->assertFalse($definition->hasOption('without-template'));
        $this->assertFalse($definition->hasOption('with-template-namespace'));
        $this->assertFalse($definition->hasOption('with-template-name'));
        $this->assertFalse($definition->hasOption('with-template-extension'));
    }

    public function testConfigureSetsExpectedOptionsWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create', null, $this->container->reveal());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION_OPT_NO_FACTORY, $option->getDescription());

        $this->assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_ACTION_OPT_NO_REGISTER, $option->getDescription());

        $this->assertFalse($definition->hasOption('without-template'));
        $this->assertFalse($definition->hasOption('with-template-namespace'));
        $this->assertFalse($definition->hasOption('with-template-name'));
        $this->assertFalse($definition->hasOption('with-template-extension'));
    }

    public function testConfigureSetsExpectedTemplateOptionsWhenRequestingAHandlerAndRendererIsPresent()
    {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = new CreateHandlerCommand('handler:create', null, $this->container->reveal());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('without-template'));
        $option = $definition->getOption('without-template');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITHOUT_TEMPLATE, $option->getDescription());

        $this->assertTrue($definition->hasOption('with-template-namespace'));
        $option = $definition->getOption('with-template-namespace');
        $this->assertTrue($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_NAMESPACE, $option->getDescription());

        $this->assertTrue($definition->hasOption('with-template-name'));
        $option = $definition->getOption('with-template-name');
        $this->assertTrue($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_NAME, $option->getDescription());

        $this->assertTrue($definition->hasOption('with-template-extension'));
        $option = $definition->getOption('with-template-extension');
        $this->assertTrue($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_EXTENSION, $option->getDescription());
    }

    public function testConfigureSetsExpectedTemplateOptionsWhenRequestingAnActionAndRendererIsPresent()
    {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = new CreateHandlerCommand('action:create', null, $this->container->reveal());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('without-template'));
        $option = $definition->getOption('without-template');
        $this->assertFalse($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITHOUT_TEMPLATE, $option->getDescription());

        $this->assertTrue($definition->hasOption('with-template-namespace'));
        $option = $definition->getOption('with-template-namespace');
        $this->assertTrue($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_NAMESPACE, $option->getDescription());

        $this->assertTrue($definition->hasOption('with-template-name'));
        $option = $definition->getOption('with-template-name');
        $this->assertTrue($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_NAME, $option->getDescription());

        $this->assertTrue($definition->hasOption('with-template-extension'));
        $option = $definition->getOption('with-template-extension');
        $this->assertTrue($option->acceptValue());
        $this->assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_EXTENSION, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
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

        $method = $this->reflectExecuteMethod($command);

        $this->assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testSuccessfulExecutionEmitsExpectedMessagesWhenRequestingAnAction()
    {
        $command = new CreateHandlerCommand('action:create', null, $this->container->reveal());
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication('Foo\TestAction')->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestAction', [])
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

        $method = $this->reflectExecuteMethod($command);

        $this->assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function commandCreatingTemplate()
    {
        $substitutions = [
            '%template-namespace%' => 'foo',
            '%template-name%' => 'test',
        ];
        return [
            'handler' => ['handler:create', 'Foo\TestHandler', 'foo', 'test', 'foo::test', $substitutions],
            'action'  => ['action:create', 'Foo\TestHandler', 'foo', 'test', 'foo::test', $substitutions],
        ];
    }

    /**
     * @dataProvider commandCreatingTemplate
     */
    public function testCommandWillGenerateTemplateWhenRendererIsRegistered(
        string $commandName,
        string $className,
        string $templateNamespace,
        string $templateName,
        string $generatedTemplate,
        array $expectedSubstitutions
    ) {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand($commandName);
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', $expectedSubstitutions)
            ->andReturn(__DIR__);

        $template = new Template(__FILE__, $generatedTemplate);
        $templateGenerator = Mockery::mock('overload:' . CreateTemplate::class);
        $templateGenerator->shouldReceive('generateTemplate')
            ->once()
            ->with('Foo\TestHandler', $templateNamespace, $templateName, null)
            ->andReturn($template);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('without-template')->willReturn(false);
        $this->input->getOption('with-template-namespace')->willReturn(null);
        $this->input->getOption('with-template-name')->willReturn(null);
        $this->input->getOption('with-template-extension')->willReturn(null);
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created template ' . $generatedTemplate . ' in file ' . __FILE__))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        $this->assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function commandCreatingTemplateWithCustomName()
    {
        $templateNamespace = 'custom';
        $templateName = 'also-custom';
        $generatedTemplate = sprintf('%s::%s', $templateNamespace, $templateName);
        $templateExtension = 'XHTML';
        $substitutions = [
            '%template-namespace%' => $templateNamespace,
            '%template-name%' => $templateName,
        ];
        return [
            // @codingStandardsIgnoreStart
            'handler' => ['handler:create', 'Foo\TestHandler', $templateNamespace, $templateName, $generatedTemplate, $templateExtension, $substitutions],
            'action'  => ['action:create', 'Foo\TestAction', $templateNamespace, $templateName, $generatedTemplate, $templateExtension, $substitutions],
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * @dataProvider commandCreatingTemplateWithCustomName
     */
    public function testCommandWillGenerateTemplateWithProvidedOptionsWhenRendererIsRegistered(
        string $commandName,
        string $className,
        string $templateNamespace,
        string $templateName,
        string $generatedTemplate,
        string $templateExtension,
        array $expectedSubstitutions
    ) {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand($commandName);
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', $expectedSubstitutions)
            ->andReturn(__DIR__);

        $template = new Template(__FILE__, $generatedTemplate);
        $templateGenerator = Mockery::mock('overload:' . CreateTemplate::class);
        $templateGenerator->shouldReceive('generateTemplate')
            ->once()
            ->with('Foo\TestHandler', $templateNamespace, $templateName, $templateExtension)
            ->andReturn($template);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('without-template')->willReturn(false);
        $this->input->getOption('with-template-namespace')->willReturn($templateNamespace);
        $this->input->getOption('with-template-name')->willReturn($templateName);
        $this->input->getOption('with-template-extension')->willReturn($templateExtension);
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created template ' . $generatedTemplate . ' in file ' . __FILE__))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        $this->assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @dataProvider commandCreatingTemplateWithCustomName
     */
    public function testCommandWillNotGenerateTemplateWithProvidedOptionsWhenWithoutTemplateOptionProvided(
        string $commandName,
        string $className,
        string $templateNamespace,
        string $templateName,
        string $templateExtension
    ) {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand($commandName);
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andReturn(__DIR__);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('without-template')->willReturn(true);
        $this->input->getOption('with-template-namespace')->shouldNotBeCalled();
        $this->input->getOption('with-template-name')->shouldNotBeCalled();
        $this->input->getOption('with-template-extension')->shouldNotBeCalled();
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created template'))
            ->shouldNotBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        $this->assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateHandlerToBubbleUp()
    {
        $command = $this->createCommand();
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andThrow(CreateHandlerException::class, 'ERROR THROWN');

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
