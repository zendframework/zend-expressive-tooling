<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\ScanForErrorMiddleware;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;
use Zend\Expressive\Tooling\ScanForErrorMiddleware\Command;
use Zend\Stdlib\ConsoleHelper;

class CommandTest extends TestCase
{
    const CLASS_INVOKING_ERROR = <<< 'EOC'
<?php
namespace Foo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class InvokeErrorMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $next($request, $response, 'error');
    }
}
EOC;

    public $commandName = 'scanner-command';

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $path;

    /** @var ConsoleHelper|ProphecyInterface */
    private $console;

    /** @var Command */
    private $command;

    public function setUp()
    {
        $this->dir = vfsStream::setup($this->commandName);
        $this->path = vfsStream::url($this->commandName);
        $this->console = $this->prophesize(ConsoleHelper::class);
        $this->command = new Command($this->commandName, $this->console->reveal());
    }

    public function assertHelp($resource = STDOUT)
    {
        $this->console
            ->writeLine(
                Argument::containingString($this->commandName . ' <command> [options]'),
                true,
                $resource
            )
            ->shouldBeCalled();
    }

    public function helpRequests()
    {
        return [
            'no-args'        => [[]],
            'help-arg'       => [['help']],
            'help-flag'      => [['-h']],
            'help-opt'       => [['--help']],
            'scan-help'      => [['scan', 'help']],
            'scan-help-flag' => [['scan', '-h']],
            'scan-help-arg'  => [['scan', '--help']],
        ];
    }

    /**
     * @dataProvider helpRequests
     *
     * @param array $args
     */
    public function testHelpRequestEmitsHelp(array $args)
    {
        $this->assertHelp();
        $this->assertEquals(0, $this->command->process($args));
    }

    public function testUnknownCommandDisplaysErrorAndHelp()
    {
        $this->console
            ->writeLine(
                Argument::containingString('Unknown command'),
                true,
                STDERR
            )
            ->shouldBeCalled();
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $this->command->process(['unknown']));
    }

    public function testUnknownScanOptionDisplaysErrorAndHelp()
    {
        $this->console
            ->writeLine(
                Argument::containingString('Invalid options provided'),
                true,
                STDERR
            )
            ->shouldBeCalled();
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $this->command->process(['scan', '--unknown']));
    }

    public function testMissingDirValueDisplaysErrorAndHelp()
    {
        $this->console
            ->writeLine(
                Argument::containingString('--dir was missing an argument'),
                true,
                STDERR
            )
            ->shouldBeCalled();
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $this->command->process(['scan', '--dir']));
    }

    public function testNonDirArgumentDisplaysErrorAndHelp()
    {
        $this->console
            ->writeLine(
                Argument::containingString('Invalid --dir argument "invalid"'),
                true,
                STDERR
            )
            ->shouldBeCalled();
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $this->command->process(['scan', '--dir', 'invalid']));
    }

    public function testNonReadableDirArgumentDisplaysErrorAndHelp()
    {
        vfsStream::newDirectory('foo', 0111)->at($this->dir);
        $path = $this->path . '/foo';

        $this->console
            ->writeLine(
                Argument::containingString('Invalid --dir argument "' . $path . '"'),
                true,
                STDERR
            )
            ->shouldBeCalled();
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $this->command->process(['scan', '--dir', $path]));
    }

    public function testScanThatFindsNothingEmitsOnlyDoneMessageAndReturnsZero()
    {
        $this->console
            ->writeLine('<info>Scanning for error middleware or error middleware invocation...</info>')
            ->shouldBeCalled();
        $this->console
            ->writeLine('')
            ->shouldBeCalled();
        $this->console
            ->writeLine('<info>Done!</info>')
            ->shouldBeCalled();

        $this->assertEquals(0, $this->command->process(['scan', '--dir', $this->path]));
    }

    public function testScanThatFindsItemsEmitsInformationMessagesAndReturnsZero()
    {
        vfsStream::newFile('src/InvokeErrorMiddleware.php')
            ->at($this->dir)
            ->setContent(self::CLASS_INVOKING_ERROR);

        $this->console
            ->writeLine('<info>Scanning for error middleware or error middleware invocation...</info>')
            ->shouldBeCalled();
        $this->console
            ->writeLine('')
            ->shouldBeCalled(); // do not care how many times
        // Scanner emits the following:
        $this->console
            ->writeLine(Argument::containingString('<error>call to $next with an error'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('<error>1 file contained error'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('Check the above logs'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Command::TEMPLATE_RESPONSE_DETAILS)
            ->shouldBeCalled();
        $this->console
            ->writeLine('<info>Done!</info>')
            ->shouldBeCalled();

        $this->assertEquals(0, $this->command->process(['scan', '--dir', $this->path]));
    }
}
