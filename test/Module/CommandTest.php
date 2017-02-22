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
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionObject;
use Zend\Expressive\Tooling\Module\Command;
use Zend\Expressive\Tooling\Module\Exception;
use Zend\Stdlib\ConsoleHelper;

class CommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use PHPMock;

    const TEST_COMMAND_NAME = 'expressive-module';

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var ConsoleHelper|ObjectProphecy */
    private $console;

    /** @var Command */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->dir = vfsStream::setup('project');

        $this->console = $this->prophesize(ConsoleHelper::class);

        $this->command = new Command(self::TEST_COMMAND_NAME, $this->console->reveal());
        $this->setProjectDir($this->command, $this->dir->url());
    }

    public function helpRequests()
    {
        return [
            'no-args'                        => [[]],
            'help-command'                   => [['help']],
            'help-option'                    => [['--help']],
            'help-flag'                      => [['-h']],
            'create-command-help-option'     => [['create', '--help']],
            'create-command-help-flag'       => [['create', '-h']],
            'register-command-help-option'   => [['register', '--help']],
            'register-command-help-flag'     => [['register', '-h']],
            'deregister-command-help-option' => [['deregister', '--help']],
            'deregister-command-help-flag'   => [['deregister', '-h']],
        ];
    }

    /**
     * @dataProvider helpRequests
     *
     * @param array $args
     */
    public function testHelpRequestsEmitHelpToStdout(array $args)
    {
        $this->assertHelpOutput();
        $this->assertEquals(0, $this->command->process($args));
    }

    public function argument()
    {
        return [
            // $action,    $argument,        $value,          $propertyName, $expectedValue
            ['create',     '--composer',     'foo/bar',       'composer',    'foo/bar'],
            ['create',     '-c',             'foo/bar',       'composer',    'foo/bar'],
            ['create',     '--modules-path', './foo/modules', 'modulesPath', 'foo/modules'],
            ['create',     '-p',             'bar\path',      'modulesPath', 'bar/path'],
            ['deregister', '--composer',     'foo/bar',       'composer',    'foo/bar'],
            ['deregister', '-c',             'foo/bar',       'composer',    'foo/bar'],
            ['deregister', '--modules-path', 'foo/modules',   'modulesPath', 'foo/modules'],
            ['deregister', '-p',             'bar/path',      'modulesPath', 'bar/path'],
            ['register',   '--composer',     'foo/bar',       'composer',    'foo/bar'],
            ['register',   '-c',             'foo/bar',       'composer',    'foo/bar'],
            ['register',   '--modules-path', 'foo/modules',   'modulesPath', 'foo/modules'],
            ['register',   '-p',             'bar/path',      'modulesPath', 'bar/path'],
        ];
    }

    /**
     * @dataProvider argument
     *
     * @param string $action
     * @param string $argument
     * @param string $value
     * @param string $propertyName
     * @param string $expectedValue
     */
    public function testArgumentIsSetAndHasExpectedValue($action, $argument, $value, $propertyName, $expectedValue)
    {
        $this->command->process([$action, $argument, $value, 'module-name']);

        $this->assertAttributeSame($expectedValue, $propertyName, $this->command);
    }

    public function testDefaultArgumentsValues()
    {
        $this->assertAttributeSame('src', 'modulesPath', $this->command);
        $this->assertAttributeSame('composer', 'composer', $this->command);
    }

    public function testUnknownCommandEmitsHelpToStderrWithErrorMessage()
    {
        $this->console
            ->writeErrorMessage(Argument::containingString('Unknown command'))
            ->shouldBeCalled();
        $this->assertHelpOutput(STDERR);

        $this->assertEquals(1, $this->command->process(['foo', 'bar']));
    }

    public function action()
    {
        return [
            'create'     => ['create'],
            'deregister' => ['deregister'],
            'register'   => ['register'],
        ];
    }

    /**
     * @dataProvider action
     *
     * @param string $action
     */
    public function testCommandErrorIfNoModuleNameProvided($action)
    {
        $this->console
            ->writeErrorMessage(Argument::containingString('Invalid module name'))
            ->shouldBeCalled();
        $this->assertHelpOutput(STDERR);

        $this->assertEquals(1, $this->command->process([$action]));
    }

    /**
     * @dataProvider action
     *
     * @param string $action
     */
    public function testCommandErrorIfInvalidNumberOfArgumentsProvided($action)
    {
        $this->console
            ->writeErrorMessage(Argument::containingString('Invalid arguments'))
            ->shouldBeCalled();
        $this->assertHelpOutput(STDERR);

        $this->assertEquals(1, $this->command->process([$action, 'invalid', 'module-name']));
    }

    /**
     * @dataProvider action
     *
     * @param string $action
     */
    public function testCommandErrorIfUnknownArgumentProvided($action)
    {
        $this->console
            ->writeErrorMessage(Argument::containingString('Unknown argument "--invalid" provided'))
            ->shouldBeCalled();
        $this->assertHelpOutput(STDERR);

        $this->assertEquals(1, $this->command->process([$action, '--invalid', 'value', 'module-name']));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @dataProvider action
     *
     * @param string $action
     */
    public function testCommandErrorIfModulesDirectoryDoesNotExist($action)
    {
        $this->console
            ->writeErrorMessage(Argument::containingString('Unable to determine modules directory'))
            ->shouldBeCalled();
        $this->assertHelpOutput(STDERR);
        $this->assertComposerBinaryExecutable();

        $this->assertEquals(1, $this->command->process([$action, 'module-name']));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @dataProvider action
     *
     * @param string $action
     */
    public function testCommandErrorIfComposerIsNotExecutable($action)
    {
        vfsStream::newDirectory('src')->at($this->dir);

        $this->console
            ->writeErrorMessage(Argument::containingString('Unable to determine composer binary'))
            ->shouldBeCalled();
        $this->assertHelpOutput(STDERR);
        $this->assertComposerBinaryNotExecutable();

        $this->assertEquals(1, $this->command->process([$action, 'module-name']));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorMessageWhenActionProcessThrowsException()
    {
        Mockery::mock('overload:' . MyTestingCommand::class)
            ->shouldReceive('process')
            ->with('App')
            ->andThrow(Exception\RuntimeException::class, 'Testing Exception Message')
            ->once();

        vfsStream::newDirectory('src')->at($this->dir);

        $this->console
            ->writeLine(Argument::containingString('Error during execution'), true, STDERR)
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('Testing Exception Message'), true, STDERR)
            ->shouldBeCalled();
        $this->assertNotHelpOutput(STDERR);
        $this->assertComposerBinaryExecutable();

        $this->injectCommand($this->command, 'my-command', MyTestingCommand::class);
        $this->assertEquals(1, $this->command->process(['my-command', 'App']));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSuccessCommandsChain()
    {
        Mockery::mock('overload:' . MyFirstCommand::class)
            ->shouldReceive('process')
            ->with('App')
            ->andReturn('Message First')
            ->once();

        Mockery::mock('overload:' . MySecondCommand::class)
            ->shouldReceive('process')
            ->with('App')
            ->andReturn('Message Second')
            ->once();

        vfsStream::newDirectory('src')->at($this->dir);

        $this->console
            ->writeLine(Argument::containingString('Message First'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('Message Second'))
            ->shouldBeCalled();
        $this->assertNotHelpOutput(STDERR);
        $this->assertComposerBinaryExecutable();

        $this->injectCommand($this->command, 'first-command', MyFirstCommand::class);
        $this->injectCommand($this->command, 'second-command', MySecondCommand::class);

        // Inject command chain
        $rCommand = new ReflectionObject($this->command);
        $rp = $rCommand->getProperty('commandChain');
        $rp->setAccessible(true);
        $commandChain = $rp->getValue($this->command);
        $commandChain[MyFirstCommand::Class] = MySecondCommand::class;
        $rp->setValue($this->command, $commandChain);

        $this->assertEquals(0, $this->command->process(['first-command', 'App']));
    }

    /**
     * @param Command $command
     * @param string $cmd
     * @param string $class
     * @return void
     */
    private function injectCommand(Command $command, $cmd, $class)
    {
        $rCommand = new ReflectionObject($command);
        $rp = $rCommand->getProperty('commands');
        $rp->setAccessible(true);

        $commands = $rp->getValue($command);
        $commands[$cmd] = $class;

        $rp->setValue($command, $commands);
    }

    /**
     * @param Command $command
     * @param string $dir
     * @return void
     */
    private function setProjectDir(Command $command, $dir)
    {
        $rc = new ReflectionObject($command);
        $rp = $rc->getProperty('projectDir');
        $rp->setAccessible(true);
        $rp->setValue($command, $dir);
    }

    private function assertHelpOutput($resource = STDOUT, $command = self::TEST_COMMAND_NAME)
    {
        $this->console
            ->writeLine(
                Argument::containingString($command . ' <command> [options] modulename'),
                true,
                $resource
            )
            ->shouldBeCalled();
    }

    private function assertNotHelpOutput($resource = STDOUT, $command = self::TEST_COMMAND_NAME)
    {
        $this->console
            ->writeLine(
                Argument::containingString($command . ' <command> [options] modulename'),
                true,
                $resource
            )
            ->shouldNotBeCalled();
    }

    private function assertComposerBinaryNotExecutable()
    {
        $exec = $this->getFunctionMock('Zend\Expressive\Tooling\Module', 'exec');
        $exec->expects($this->once())->willReturnCallback(function ($command, &$output, &$retValue) {
            $this->assertEquals('composer 2>&1', $command);
            $retValue = 1;
        });
    }

    private function assertComposerBinaryExecutable()
    {
        $exec = $this->getFunctionMock('Zend\Expressive\Tooling\Module', 'exec');
        $exec->expects($this->once())->willReturnCallback(function ($command, &$output, &$retValue) {
            $this->assertEquals('composer 2>&1', $command);
            $retValue = 0;
        });
    }
}
