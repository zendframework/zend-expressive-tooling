<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\CreateMiddleware;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\CreateMiddleware\CreateMiddleware;
use Zend\Expressive\Tooling\CreateMiddleware\CreateMiddlewareException;

class CreateMiddlewareTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $projectRoot;

    public function setUp()
    {
        $this->dir = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
    }

    public function testProcessRaisesExceptionWhenComposerJsonNotPresentInProjectRoot()
    {
        $generator = new CreateMiddleware();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('find a composer.json');

        $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot);
    }

    public function testProcessRaisesExceptionForMalformedComposerJson()
    {
        file_put_contents($this->projectRoot . '/composer.json', 'not-a-value');
        $generator = new CreateMiddleware();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('Unable to parse');

        $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot);
    }

    public function testProcessRaisesExceptionIfComposerJsonDoesNotDefinePsr4Autoloaders()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode(['name' => 'some/project']));
        $generator = new CreateMiddleware();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('PSR-4 autoloaders');

        $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot);
    }

    public function testProcessRaisesExceptionIfComposerJsonDefinesMalformedPsr4Autoloaders()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => 'not-valid',
            ],
        ]));
        $generator = new CreateMiddleware();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('PSR-4 autoloaders');

        $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot);
    }

    public function testProcessRaisesExceptionIfClassDoesNotMatchAnyAutoloadableNamespaces()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/App/',
                ],
            ],
        ]));
        $generator = new CreateMiddleware();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('Unable to match');

        $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot);
    }

    public function testProcessRaisesExceptionIfUnableToCreateSubPath()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/App/',
                    'Foo\\' => 'src/Foo/src/',
                ],
            ],
        ]));
        vfsStream::newDirectory('src/Foo/src', 0555)->at($this->dir);

        $generator = new CreateMiddleware();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('Unable to create the directory');

        $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot);
    }

    public function testProcessCanCreateMiddlewareInNamespaceRoot()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/App/',
                    'Foo\\' => 'src/Foo/',
                ],
            ],
        ]));
        vfsStream::newDirectory('src/Foo/src', 0775)->at($this->dir);

        $generator = new CreateMiddleware();

        $expectedPath = vfsStream::url('project/src/Foo/BarMiddleware.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\BarMiddleware', $this->projectRoot)
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo;$#m', $classFileContents);
        $this->assertRegexp('#^class BarMiddleware implements MiddlewareInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function process\(ServerRequestInterface \$request,'
                . ' RequestHandlerInterface \$handler\) : ResponseInterface$#m',
            $classFileContents
        );
    }

    public function testProcessCanCreateMiddlewareInSubNamespacePath()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/App/',
                    'Foo\\' => 'src/Foo/',
                ],
            ],
        ]));
        vfsStream::newDirectory('src/Foo/src', 0775)->at($this->dir);

        $generator = new CreateMiddleware();

        $expectedPath = vfsStream::url('project/src/Foo/Bar/BazMiddleware.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot)
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo\\\\Bar;$#m', $classFileContents);
        $this->assertRegexp('#^class BazMiddleware implements MiddlewareInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function process\(ServerRequestInterface \$request,'
                . ' RequestHandlerInterface \$handler\) : ResponseInterface$#m',
            $classFileContents
        );
    }

    public function testProcessCanCreateMiddlewareInModuleNamespaceRoot()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/App/',
                    'Foo\\' => 'src/Foo/src/',
                ],
            ],
        ]));
        vfsStream::newDirectory('src/Foo/src', 0775)->at($this->dir);

        $generator = new CreateMiddleware();

        $expectedPath = vfsStream::url('project/src/Foo/src/BarMiddleware.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\BarMiddleware', $this->projectRoot)
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo;$#m', $classFileContents);
        $this->assertRegexp('#^class BarMiddleware implements MiddlewareInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function process\(ServerRequestInterface \$request,'
                . ' RequestHandlerInterface \$handler\) : ResponseInterface$#m',
            $classFileContents
        );
    }

    public function testProcessCanCreateMiddlewareInModuleSubNamespacePath()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/App/',
                    'Foo\\' => 'src/Foo/src/',
                ],
            ],
        ]));
        vfsStream::newDirectory('src/Foo/src', 0775)->at($this->dir);

        $generator = new CreateMiddleware();

        $expectedPath = vfsStream::url('project/src/Foo/src/Bar/BazMiddleware.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\Bar\BazMiddleware', $this->projectRoot)
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo\\\\Bar;$#m', $classFileContents);
        $this->assertRegexp('#^class BazMiddleware implements MiddlewareInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function process\(ServerRequestInterface \$request,'
                . ' RequestHandlerInterface \$handler\) : ResponseInterface$#m',
            $classFileContents
        );
    }
}
