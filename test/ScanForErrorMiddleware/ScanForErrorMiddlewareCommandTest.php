<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\ScanForErrorMiddleware;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Zend\Expressive\Tooling\ScanForErrorMiddleware\ArgvException;
use Zend\Expressive\Tooling\ScanForErrorMiddleware\ScanForErrorMiddlewareCommand;
use Zend\Expressive\Tooling\ScanForErrorMiddleware\Scanner;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ScanForErrorMiddlewareCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new ScanForErrorMiddlewareCommand('migrate:scan-for-error-middleware');
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('Scan for legacy error middleware', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(ScanForErrorMiddlewareCommand::HELP, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('dir'));
        $option = $definition->getOption('dir');
        $this->assertTrue($option->isValueRequired());
        $this->assertEquals(ScanForErrorMiddlewareCommand::HELP_OPT_DIR, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        $dir = vfsStream::setup('ErrorMiddleware');
        $path = vfsStream::url('ErrorMiddleware');
        mkdir($path . '/src');

        $scanner = Mockery::mock('overload:' . Scanner::class);
        $scanner->shouldReceive('scan')
            ->once()
            ->andReturnNull();
        $scanner->shouldReceive('count')
            ->once()
            ->andReturn(5);

        $this->input->getOption('dir')->willReturn('src');

        $this->output
            ->writeln(Argument::containingString('Scanning for error middleware'))
            ->shouldBeCalled();
        $this->output
            ->writeln('')
            ->shouldBeCalledTimes(5);
        $this->output
            ->writeln(Argument::containingString('5 files contained error middleware'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Check the above logs'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString(ScanForErrorMiddlewareCommand::TEMPLATE_RESPONSE_DETAILS))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalled();

        $this->command->setProjectDir($path);
        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testSuccessfulExecutionEmitsExpectedMessagesWhenNoErrorMiddlewareFound()
    {
        $dir = vfsStream::setup('ErrorMiddleware');
        $path = vfsStream::url('ErrorMiddleware');
        mkdir($path . '/src');

        $scanner = Mockery::mock('overload:' . Scanner::class);
        $scanner->shouldReceive('scan')
            ->once()
            ->andReturnNull();
        $scanner->shouldReceive('count')
            ->once()
            ->andReturn(0);

        $this->input->getOption('dir')->willReturn('src');

        $this->output
            ->writeln(Argument::containingString('Scanning for error middleware'))
            ->shouldBeCalled();
        $this->output
            ->writeln('')
            ->shouldBeCalledTimes(1);
        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalled();

        $this->command->setProjectDir($path);
        $method = $this->reflectExecuteMethod();

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsFromInvalidDirArgumentToBubbleUp()
    {
        $dir = vfsStream::setup('ErrorMiddleware');
        $path = vfsStream::url('ErrorMiddleware');

        $scanner = Mockery::mock('overload:' . Scanner::class);
        $scanner->shouldNotReceive('scan');
        $scanner->shouldNotReceive('count');

        $this->input->getOption('dir')->willReturn('src');

        $this->command->setProjectDir($path);
        $method = $this->reflectExecuteMethod();

        $this->expectException(ArgvException::class);
        $this->expectExceptionMessage('Invalid --dir argument');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
