<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\CreateHandler;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\CreateHandler\CreateHandler;
use Zend\Expressive\Tooling\CreateHandler\CreateHandlerException;

class CreateHandlerTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $projectRoot;

    protected function setUp() : void
    {
        $this->dir = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
    }

    public function testProcessRaisesExceptionWhenComposerJsonNotPresentInProjectRoot()
    {
        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('find a composer.json');

        $generator->process('Foo\Bar\BazHandler');
    }

    public function testProcessRaisesExceptionForMalformedComposerJson()
    {
        file_put_contents($this->projectRoot . '/composer.json', 'not-a-value');
        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('Unable to parse');

        $generator->process('Foo\Bar\BazHandler');
    }

    public function testProcessRaisesExceptionIfComposerJsonDoesNotDefinePsr4Autoloaders()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode(['name' => 'some/project']));
        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('PSR-4 autoloaders');

        $generator->process('Foo\Bar\BazHandler');
    }

    public function testProcessRaisesExceptionIfComposerJsonDefinesMalformedPsr4Autoloaders()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => 'not-valid',
            ],
        ]));
        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('PSR-4 autoloaders');

        $generator->process('Foo\Bar\BazHandler');
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
        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('Unable to match');

        $generator->process('Foo\Bar\BazHandler');
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

        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('Unable to create the directory');

        $generator->process('Foo\Bar\BazHandler');
    }

    public function testProcessCanCreateHandlerInNamespaceRoot()
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

        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $expectedPath = vfsStream::url('project/src/Foo/BarHandler.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\BarHandler')
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo;$#m', $classFileContents);
        $this->assertRegexp('#^class BarHandler implements RequestHandlerInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function handle\(ServerRequestInterface \$request\) : ResponseInterface$#m',
            $classFileContents
        );
    }

    public function testProcessCanCreateHandlerInSubNamespacePath()
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

        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $expectedPath = vfsStream::url('project/src/Foo/Bar/BazHandler.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\Bar\BazHandler')
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo\\\\Bar;$#m', $classFileContents);
        $this->assertRegexp('#^class BazHandler implements RequestHandlerInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function handle\(ServerRequestInterface \$request\) : ResponseInterface$#m',
            $classFileContents
        );
    }

    public function testProcessCanCreateHandlerInModuleNamespaceRoot()
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

        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $expectedPath = vfsStream::url('project/src/Foo/src/BarHandler.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\BarHandler')
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo;$#m', $classFileContents);
        $this->assertRegexp('#^class BarHandler implements RequestHandlerInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function handle\(ServerRequestInterface \$request\) : ResponseInterface$#m',
            $classFileContents
        );
    }

    public function testProcessCanCreateHandlerInModuleSubNamespacePath()
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

        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $expectedPath = vfsStream::url('project/src/Foo/src/Bar/BazHandler.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\Bar\BazHandler')
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertRegexp('#^\<\?php#s', $classFileContents);
        $this->assertRegexp('#^namespace Foo\\\\Bar;$#m', $classFileContents);
        $this->assertRegexp('#^class BazHandler implements RequestHandlerInterface$#m', $classFileContents);
        $this->assertRegexp(
            '#^\s{4}public function handle\(ServerRequestInterface \$request\) : ResponseInterface$#m',
            $classFileContents
        );
    }

    public function testProcessThrowsExceptionIfClassAlreadyExists()
    {
        file_put_contents($this->projectRoot . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/App/',
                ],
            ],
        ]));

        vfsStream::newDirectory('src/App/Foo', 0775)->at($this->dir);
        file_put_contents($this->projectRoot . '/src/App/Foo/BarHandler.php', 'App\Foo\BarHandler');

        $generator = new CreateHandler(CreateHandler::CLASS_SKELETON, $this->projectRoot);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('Class BarHandler already exists');

        $generator->process('App\Foo\BarHandler');
    }

    public function testTheClassSkeletonParameterOverridesTheConstant()
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

        $generator = new CreateHandler('class Foo\Bar\BazHandler', $this->projectRoot);

        $expectedPath = vfsStream::url('project/src/Foo/Bar/BazHandler.php');
        $this->assertEquals(
            $expectedPath,
            $generator->process('Foo\Bar\BazHandler')
        );

        $classFileContents = file_get_contents($expectedPath);
        $this->assertStringContainsString('class Foo\Bar\BazHandler', $classFileContents);
    }
}
