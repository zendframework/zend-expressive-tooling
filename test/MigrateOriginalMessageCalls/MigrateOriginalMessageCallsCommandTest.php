<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\MigrateOriginalMessageCalls;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\MigrateOriginalMessageCalls\ArgvException;
use Zend\Expressive\Tooling\MigrateOriginalMessageCalls\ConvertOriginalMessageCalls;
use Zend\Expressive\Tooling\MigrateOriginalMessageCalls\MigrateOriginalMessageCallsCommand;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MigrateOriginalMessageCallsCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new MigrateOriginalMessageCallsCommand('migrate:original-messages');
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('Migrate getOriginal*() calls', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(MigrateOriginalMessageCallsCommand::HELP, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('src'));
        $option = $definition->getOption('src');
        $this->assertTrue($option->isValueRequired());
        $this->assertEquals(MigrateOriginalMessageCallsCommand::HELP_OPT_SRC, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        $dir = vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');
        mkdir($path . '/src');

        $converter = Mockery::mock('overload:' . ConvertOriginalMessageCalls::class);
        $converter->shouldReceive('process')
            ->once()
            ->with($path . '/src')
            ->andReturnNull();
        $converter->shouldReceive('originalResponseFound')
            ->once()
            ->andReturn(true);

        $this->input->getOption('src')->willReturn('src');

        $this->output
            ->writeln(Argument::containingString('Scanning for usage of Stratigility HTTP message decorators'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('One or more files contained calls'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Check the above logs'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString(MigrateOriginalMessageCallsCommand::TEMPLATE_RESPONSE_DETAILS))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalled();

        $this->command->projectDir = $path;
        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testSuccessfulExecutionEmitsExpectedMessagesWhenNoConversionsMade()
    {
        $dir = vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');
        mkdir($path . '/src');

        $converter = Mockery::mock('overload:' . ConvertOriginalMessageCalls::class);
        $converter->shouldReceive('process')
            ->once()
            ->with($path . '/src')
            ->andReturnNull();
        $converter->shouldReceive('originalResponseFound')
            ->once()
            ->andReturn(false);

        $this->input->getOption('src')->willReturn('src');

        $this->output
            ->writeln(Argument::containingString('Scanning for usage of Stratigility HTTP message decorators'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalled();

        $this->command->projectDir = $path;
        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testInvalidSrcDirectoryCausesCommandToEmitErrorMessages()
    {
        $dir = vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');

        $converter = Mockery::mock('overload:' . ConvertOriginalMessageCalls::class);
        $converter->shouldNotReceive('process');
        $converter->shouldNotReceive('originalResponseFound');

        $this->input->getOption('src')->willReturn('src');

        $this->command->projectDir = $path;
        $method = $this->reflectExecuteMethod();

        $this->expectException(ArgvException::class);
        $this->expectExceptionMessage('Invalid --src argument');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
