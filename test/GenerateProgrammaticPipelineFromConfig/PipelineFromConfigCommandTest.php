<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig\Constants;
use Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig\Generator;
use Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig\PipelineFromConfigCommand;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PipelineFromConfigCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new PipelineFromConfigCommand('migrate:pipeline');
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('programmatic pipeline and routes', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(PipelineFromConfigCommand::HELP, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('config-file'));
        $option = $definition->getOption('config-file');
        $this->assertTrue($option->isValueRequired());
        $this->assertEquals(PipelineFromConfigCommand::HELP_OPT_CONFIG_FILE, $option->getDescription());
    }

    public function testSuccessfulMigrationGeneratesExpectedMessages()
    {
        $generator = Mockery::mock('overload:' . Generator::class);
        $generator->shouldReceive('process')
            ->once()
            ->with(getcwd() . '/config/config.php')
            ->andReturnNull();

        $this->output->writeln(Argument::containingString('Generating programmatic pipeline'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('Success!'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('Created ' . Constants::PATH_CONFIG))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('Created ' . Constants::PATH_PIPELINE))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('Created ' . Constants::PATH_ROUTES))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('Updated ' . Constants::PATH_APPLICATION))->shouldBeCalled();

        $method = $this->reflectExecuteMethod($this->command);

        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }
}
