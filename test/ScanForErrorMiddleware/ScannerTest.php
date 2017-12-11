<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\ScanForErrorMiddleware;

use Countable;
use IteratorAggregate;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\ScanForErrorMiddleware\ErrorMiddlewareFilter;
use Zend\Expressive\Tooling\ScanForErrorMiddleware\Scanner;
use Zend\Stratigility\ErrorMiddlewareInterface;

class ScannerTest extends TestCase
{
    const CLASS_IMPLEMENTING_INTERFACE = <<< 'EOC'
<?php
namespace Foo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\ErrorMiddlewareInterface;

class ErrorMiddleware implements ErrorMiddlewareInterface
{
    public function __invoke($error, ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
    }
}
EOC;

    const CLASS_DUCK_TYPING = <<< 'EOC'
<?php
namespace Foo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DuckTypedErrorMiddleware
{
    public function __invoke($error, ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
    }
}
EOC;

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

    const BASIC_MIDDLEWARE = <<< 'EOC'
<?php
namespace Foo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BasicMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
EOC;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $path;

    /** @var OutputInterface|ObjectProphecy */
    private $console;

    /** @var Scanner */
    private $scanner;

    public function setUp()
    {
        $this->dir = vfsStream::setup('scanner');
        $this->path = vfsStream::url('scanner');
        $this->console = $this->prophesize(OutputInterface::class);
        $this->scanner = new Scanner($this->path, $this->console->reveal());
    }

    public function testImplementsCountable()
    {
        $this->assertInstanceOf(Countable::class, $this->scanner);
    }

    public function testCountIsZeroByDefault()
    {
        $this->assertCount(0, $this->scanner);
    }

    public function testImplementsIteratorAggregateAsAnErrorMiddlewareFilter()
    {
        $this->assertInstanceOf(IteratorAggregate::class, $this->scanner);
        $iterator = $this->scanner->getIterator();
        $this->assertInstanceOf(ErrorMiddlewareFilter::class, $iterator);
    }

    public function testScanningEmitsInfoToConsoleWhenEncounteringFilesOfInterest()
    {
        vfsStream::newFile('src/ErrorMiddleware.php')
            ->at($this->dir)
            ->setContent(self::CLASS_IMPLEMENTING_INTERFACE);
        vfsStream::newFile('src/DuckTypedErrorMiddleware.php')
            ->at($this->dir)
            ->setContent(self::CLASS_DUCK_TYPING);
        vfsStream::newFile('src/InvokeErrorMiddleware.php')
            ->at($this->dir)
            ->setContent(self::CLASS_INVOKING_ERROR);
        vfsStream::newFile('src/BasicMiddleware.php')
            ->at($this->dir)
            ->setContent(self::BASIC_MIDDLEWARE);

        $this->console
            ->writeln(
                Argument::that(function ($arg) {
                    if (false === strpos($arg, 'src/ErrorMiddleware.php')) {
                        return false;
                    }
                    if (false === strpos(
                        $arg,
                        sprintf('<error>implementing %s</error>', ErrorMiddlewareInterface::class)
                    )) {
                        return false;
                    }
                    return true;
                })
            )
            ->shouldBeCalled();

        $this->console
            ->writeln(
                Argument::that(function ($arg) {
                    if (false === strpos($arg, 'src/DuckTypedErrorMiddleware.php')) {
                        return false;
                    }
                    if (false === strpos($arg, '<error>implementing invokable error middleware</error>')) {
                        return false;
                    }
                    return true;
                })
            )
            ->shouldBeCalled();

        $this->console
            ->writeln(
                Argument::that(function ($arg) {
                    if (false === strpos($arg, 'src/InvokeErrorMiddleware.php')) {
                        return false;
                    }
                    if (false === strpos($arg, '<error>call to $next with an error argument</error>')) {
                        return false;
                    }
                    return true;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->scanner->scan());

        return $this->scanner;
    }

    /**
     * @depends testScanningEmitsInfoToConsoleWhenEncounteringFilesOfInterest
     *
     * @param Scanner $scanner
     */
    public function testScanningIncrementsCount(Scanner $scanner)
    {
        $this->assertCount(3, $scanner);
    }
}
