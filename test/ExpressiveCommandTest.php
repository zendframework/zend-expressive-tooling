<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\Expressive\Tooling\ExpressiveCommand;
use Zend\Stdlib\ConsoleHelper;

class ExpressiveCommandTest extends TestCase
{
    /** @var ConsoleHelper|\Prophecy\Prophecy\ObjectProphecy */
    private $console;

    protected function setUp()
    {
        $this->console = $this->prophesize(ConsoleHelper::class);
        $this->command = new ExpressiveCommand(
            ExpressiveCommand::DEFAULT_COMMAND_NAME,
            $this->console->reveal()
        );
    }

    public function helpArguments()
    {
        return [
            'no-args'   => [[]],
            'long-arg'  => [['--help']],
            'short-arg' => [['-h']],
            'command'   => [['help']],
        ];
    }

    /**
     * @dataProvider helpArguments
     * @param array $args Arguments to pass to the command
     */
    public function testHelpRequestsEmitHelp(array $args)
    {
        $this->console
            ->writeLine(
                Argument::containingString(ExpressiveCommand::DEFAULT_COMMAND_NAME . ' <command>'),
                true,
                STDOUT
            )
            ->shouldBeCalled();

        $this->assertSame(0, $this->command->process($args));
    }

    public function testPassingInvalidCommandEmitsErrorAndHelpToStderr()
    {
        $this->console
            ->writeLine(
                Argument::containingString('Invalid command'),
                true,
                STDERR
            )
            ->shouldBeCalled();
        $this->console
            ->writeLine(
                Argument::containingString(ExpressiveCommand::DEFAULT_COMMAND_NAME . ' <command>'),
                true,
                STDERR
            )
            ->shouldBeCalled();

        $this->assertSame(1, $this->command->process(['unknown-command']));
    }

    public function commandHelpArguments()
    {
        // @codingStandardsIgnoreStart
        return [
            'pre-create-middleware'          => [['help', 'create-middleware'], 'expressive create-middleware'],
            'pre-migrate-original-messages'  => [['help', 'migrate-original-messages'], 'expressive migrate-original-messages'],
            'pre-module'                     => [['help', 'module'], 'expressive module'],
            'pre-pipeline-from-config'       => [['help', 'pipeline-from-config'], 'expressive pipeline-from-config'],
            'pre-scan-for-error-middleware'  => [['help', 'scan-for-error-middleware'], 'expressive scan-for-error-middleware'],
            'post-create-middleware'         => [['create-middleware', 'help'], 'create-middleware'],
            'post-migrate-original-messages' => [['migrate-original-messages', 'help'], 'migrate-original-messages'],
            'post-module'                    => [['module', 'help'], 'expressive module'],
            'post-pipeline-from-config'      => [['pipeline-from-config', 'help'], 'expressive pipeline-from-config'],
            'post-scan-for-error-middleware' => [['scan-for-error-middleware', 'help'], 'expressive scan-for-error-middleware'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider commandHelpArguments
     * @param array $args Arguments to pass to the command
     * @param string $expected String to expect in console output
     */
    public function testCanRequestCommandHelp(array $args, $expected)
    {
        $this->console
            ->writeLine(
                Argument::containingString($expected),
                true,
                STDOUT
            )
            ->shouldBeCalled();

        $this->assertSame(0, $this->command->process($args));
    }

    /**
     * @todo Should likely test more commands. I've tested all of them manually,
     *       but didn't want to set up tests for each of the current commands
     *       due to testing complexity. They're likely fine, but we may run
     *       against edge cases.
     */
    public function testCanDispatchKnownCommands()
    {
        $dir = vfsStream::setup('project');
        $projectRoot = vfsStream::url('project');
        $this->command->projectDir = $projectRoot;
        file_put_contents($projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'Foo\\' => 'src/Foo/',
                ],
            ],
        ]));
        vfsStream::newDirectory('src/Foo', 0775)->at($dir);

        $this->console
            ->writeLine(
                Argument::containingString('Creating middleware Foo\BarMiddleware...')
            )
            ->shouldBeCalled();

        $this->console
            ->writeLine(
                Argument::containingString('Success!')
            )
            ->shouldBeCalled();

        $expectedPath = vfsStream::url('project/src/Foo/BarMiddleware.php');
        $this->console
            ->writeLine(
                Argument::containingString('Created class Foo\BarMiddleware, in file ' . $expectedPath)
            )
            ->shouldBeCalled();

        $this->assertSame(0, $this->command->process(['create-middleware', 'Foo\BarMiddleware']));
        $this->assertFileExists($expectedPath);
    }
}
