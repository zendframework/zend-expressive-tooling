<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Zend\Expressive\Tooling\MigrateInteropMiddleware\ArgvException;
use Zend\Expressive\Tooling\MigrateInteropMiddleware\ConvertInteropMiddleware;
use Zend\Expressive\Tooling\MigrateInteropMiddleware\MigrateInteropMiddlewareCommand;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MigrateInteropMiddlewareCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
    private $output;

    /** @var MigrateInteropMiddlewareCommand */
    private $command;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new MigrateInteropMiddlewareCommand('migrate:interop-middleware');
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('Migrate http-interop middleware and delegators', $this->command->getDescription());
    }

    private function getConstantValue(string $const, string $class = MigrateInteropMiddlewareCommand::class)
    {
        $r = new ReflectionClass($class);

        return $r->getConstant($const);
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals($this->getConstantValue('HELP'), $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('src'));
        $option = $definition->getOption('src');
        $this->assertTrue($option->isValueRequired());
        $this->assertEquals($this->getConstantValue('HELP_OPT_SRC'), $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');
        mkdir($path . '/src');

        $converter = Mockery::mock('overload:' . ConvertInteropMiddleware::class);
        $converter->shouldReceive('process')
            ->once()
            ->with($path . '/src')
            ->andReturnNull();

        $this->input->getOption('src')->willReturn('src');

        $this->output
            ->writeln(Argument::containingString('Scanning for usage of http-interop middleware...'))
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

    public function testAllowsExceptionsFromInvalidSrcDirectoryArgumentToBubbleUp()
    {
        vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');

        $converter = Mockery::mock('overload:' . ConvertInteropMiddleware::class);
        $converter->shouldNotReceive('process');

        $this->input->getOption('src')->willReturn('src');

        $this->command->setProjectDir($path);
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
