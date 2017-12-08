<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\ScanForErrorMiddleware;

use ArrayIterator;
use DirectoryIterator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Zend\Expressive\Tooling\ScanForErrorMiddleware\ErrorMiddlewareFilter;

class ErrorMiddlewareFilterTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $dirPath;

    public function setUp()
    {
        $this->dir = vfsStream::setup('error-middleware-filter');
        $this->dirPath = vfsStream::url('error-middleware-filter');
    }

    public function testIgnoresNonSplFileInfoItems()
    {
        $iterator = new ArrayIterator(['foo', 'bar', 'baz']);
        $filter = new ErrorMiddlewareFilter($iterator);

        $items = iterator_to_array($filter);
        $this->assertCount(0, $items);
    }

    /**
     * - a directory
     */
    public function testIgnoresDirectories()
    {
        vfsStream::newDirectory('foo', 0755)
            ->at($this->dir);
        vfsStream::newDirectory('bar', 0755)
            ->at($this->dir);
        $iterator = new DirectoryIterator($this->dirPath);
        $filter = new ErrorMiddlewareFilter($iterator);

        $items = iterator_to_array($filter);
        $this->assertCount(0, $items);
    }

    /**
     * - a file without the .php extension
     */
    public function testIgnoresFilesWithoutPhpExtension()
    {
        vfsStream::newFile('foo/bar.txt')
            ->at($this->dir);
        $rdi = new RecursiveDirectoryIterator($this->dirPath);
        $rii = new RecursiveIteratorIterator($rdi);
        $filter = new ErrorMiddlewareFilter($rii);

        $items = iterator_to_array($filter);
        $this->assertCount(0, $items);
    }

    /**
     * - a PHP file that is not readable
     */
    public function testIgnoresUnreadablePhpFiles()
    {
        vfsStream::newFile('foo/bar.php', 0111)
            ->at($this->dir);
        $iterator = new DirectoryIterator($this->dirPath);
        $filter = new ErrorMiddlewareFilter($iterator);

        $items = iterator_to_array($filter);
        $this->assertCount(0, $items);
    }

    /**
     * - a PHP file that contains no classes
     */
    public function testIgnoresPhpFilesContainingNoClasses()
    {
        vfsStream::newFile('foo/bar.php')
            ->at($this->dir)
            ->setContent('<' . "?php\nphpinfo();");
        $rdi = new RecursiveDirectoryIterator($this->dirPath);
        $rii = new RecursiveIteratorIterator($rdi);
        $filter = new ErrorMiddlewareFilter($rii);

        $items = iterator_to_array($filter);
        $this->assertCount(0, $items);
    }

    /**
     * - a PHP file that contains a class that does not implement the
     *   ErrorMiddlewareInterface or __invoke, and which does not have any
     *   methods with a $next argument
     */
    public function testIgnoresPhpFilesWithClassesNotImplementingErrorMiddlewareOrCallingNextWithAnError()
    {
        $classFileContents = <<< 'EOC'
<?php
namespace Foo;

class Bar
{
    public function execute(callable $next)
    {
        return $next();
    }
}
EOC;
        vfsStream::newFile('foo/Bar.php')
            ->at($this->dir)
            ->setContent($classFileContents);
        $rdi = new RecursiveDirectoryIterator($this->dirPath);
        $rii = new RecursiveIteratorIterator($rdi);
        $filter = new ErrorMiddlewareFilter($rii);

        $items = iterator_to_array($filter);
        $this->assertCount(0, $items);
    }

    /**
     * - a PHP file with a class implementing the ErrorMiddlewareInterface
     */
    public function testAcceptsPhpFilesWithClassesImplementingErrorMiddlewareInterface()
    {
        $classFileContents = <<< 'EOC'
<?php
namespace Foo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\ErrorMiddlewareInterface;

class Bar implements ErrorMiddlewareInterface
{
    public function __invoke($error, ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
        return $out($request, $response);
    }
}
EOC;
        vfsStream::newFile('foo/Bar.php')
            ->at($this->dir)
            ->setContent($classFileContents);
        $rdi = new RecursiveDirectoryIterator($this->dirPath);
        $rii = new RecursiveIteratorIterator($rdi);
        $filter = new ErrorMiddlewareFilter($rii);

        $items = iterator_to_array($filter);
        $this->assertCount(1, $items);
        $found = array_pop($items);
        $this->assertInstanceOf(SplFileInfo::class, $found);
        $this->assertContains('foo/Bar.php', (string) $found);
    }

    /**
     * - a PHP file that contains a class implementing __invoke with the error
     *   middleware signature
     */
    public function testAcceptsPhpFilesDuckTypingErrorMiddlewareInterface()
    {
        $classFileContents = <<< 'EOC'
<?php
namespace Foo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Bar
{
    public function __invoke($error, ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
EOC;
        vfsStream::newFile('foo/Bar.php')
            ->at($this->dir)
            ->setContent($classFileContents);
        $rdi = new RecursiveDirectoryIterator($this->dirPath);
        $rii = new RecursiveIteratorIterator($rdi);
        $filter = new ErrorMiddlewareFilter($rii);

        $items = iterator_to_array($filter);
        $this->assertCount(1, $items);
        $found = array_pop($items);
        $this->assertInstanceOf(SplFileInfo::class, $found);
        $this->assertContains('foo/Bar.php', (string) $found);
    }

    /**
     * - a PHP file that contains a class with a method accepting $next, but
     *   which DOES NOT call it with the error argument
     */
    public function testIgnoresPhpFilesWithMethodsAcceptingNextButNotInvokingItWithAnError()
    {
        $classFileContents = <<< 'EOC'
<?php
namespace Foo;

class Bar
{
    public function execute(callable $next)
    {
        return $next($this->request, $this->response);
    }
}
EOC;
        vfsStream::newFile('foo/Bar.php')
            ->at($this->dir)
            ->setContent($classFileContents);
        $rdi = new RecursiveDirectoryIterator($this->dirPath);
        $rii = new RecursiveIteratorIterator($rdi);
        $filter = new ErrorMiddlewareFilter($rii);

        $items = iterator_to_array($filter);
        $this->assertCount(0, $items);
    }

    /**
     * - a PHP file that contains a class with a method accepting $next, and
     *   which DOES call it with the error argument
     */
    public function testAcceptsPhpFilesWithMethodsAcceptingNextAndInvokingItWithAnError()
    {
        $classFileContents = <<< 'EOC'
<?php
namespace Foo;

class Bar
{
    public function execute(callable $next)
    {
        return $next($this->request, $this->response, 'error');
    }
}
EOC;
        vfsStream::newFile('foo/Bar.php')
            ->at($this->dir)
            ->setContent($classFileContents);
        $rdi = new RecursiveDirectoryIterator($this->dirPath);
        $rii = new RecursiveIteratorIterator($rdi);
        $filter = new ErrorMiddlewareFilter($rii);

        $items = iterator_to_array($filter);
        $this->assertCount(1, $items);
        $found = array_pop($items);
        $this->assertInstanceOf(SplFileInfo::class, $found);
        $this->assertContains('foo/Bar.php', (string) $found);
    }
}
