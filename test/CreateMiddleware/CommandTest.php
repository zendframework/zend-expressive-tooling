<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\CreateMiddleware;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\Expressive\Tooling\CreateMiddleware\Command;
use Zend\Stdlib\ConsoleHelper;

class CommandTest extends TestCase
{
    /** @var ConsoleHelper|\Prophecy\Prophecy\ObjectProphecy */
    private $console;

    protected function setUp()
    {
        $this->console = $this->prophesize(ConsoleHelper::class);
        $this->command = new Command(Command::DEFAULT_COMMAND_NAME, $this->console->reveal());
    }

    public function helpArguments()
    {
        return [
            'no-args'               => [[]],
            'long-arg'              => [['--help']],
            'short-arg'             => [['-h']],
            'command'               => [['help']],
            'long-arg-after-class'  => [[__CLASS__, '--help']],
            'short-arg-after-class' => [[__CLASS__, '-h']],
            'command-after-class'   => [[__CLASS__, 'help']],
        ];
    }

    /**
     * @dataProvider helpArguments
     */
    public function testHelpRequestsEmitHelp(array $args)
    {
        $this->console
            ->writeLine(
                Argument::containingString(Command::DEFAULT_COMMAND_NAME . ' [options] <middleware>'),
                true,
                STDOUT
            )
            ->shouldBeCalled();

        $this->assertSame(0, $this->command->process($args));
    }

    public function testInvokingCommandWithInvalidSetupResultsInErrorMessage()
    {
        $dir = vfsStream::setup('project');
        $projectRoot = vfsStream::url('project');
        $this->command->projectDir = $projectRoot;

        $this->console
            ->writeLine(
                Argument::containingString('Creating middleware Foo\BarMiddleware...')
            )
            ->shouldBeCalled();

        $this->console
            ->writeLine(
                Argument::containingString('Error during generation'),
                true,
                STDERR
            )
            ->shouldBeCalled();

        $this->console
            ->writeLine(
                Argument::containingString('composer.json'),
                true,
                STDERR
            )
            ->shouldBeCalled();

        $this->assertSame(1, $this->command->process(['Foo\BarMiddleware']));
    }

    public function testSuccessfulInvocationResultsInSuccessStatus()
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

        $this->assertSame(0, $this->command->process(['Foo\BarMiddleware']));
        $this->assertFileExists($expectedPath);
    }
}
