<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Module;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\Module\Create;
use Zend\Expressive\Tooling\Module\RuntimeException;

class CreateTest extends TestCase
{
    use PHPMock;

    /** @var string */
    private $composer = 'my-composer';

    /** @var Create */
    private $command;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var vfsStreamDirectory */
    private $modulesDir;

    /** @var string */
    private $modulesPath = 'my-modules';

    /** @var string */
    private $projectDir;

    protected function setUp()
    {
        parent::setUp();

        $this->dir = vfsStream::setup('project');
        $this->modulesDir = vfsStream::newDirectory($this->modulesPath)->at($this->dir);
        $this->projectDir = vfsStream::url('project');
        $this->command = new Create();
    }

    public function testErrorsWhenModuleDirectoryAlreadyExists()
    {
        vfsStream::newDirectory('MyApp')->at($this->modulesDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module "MyApp" already exists');
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testErrorsWhenCannotCreateModuleDirectory()
    {
        $baseModulePath = sprintf('%s/my-modules/MyApp', $this->dir->url());

        $mkdir = $this->getFunctionMock('Zend\Expressive\Tooling\Module', 'mkdir');
        $mkdir->expects($this->once())
            ->with($baseModulePath)
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Module directory "%s" cannot be created',
            $baseModulePath
        ));
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testErrorsWhenCannotCreateModuleSrcDirectory()
    {
        $baseModulePath = sprintf('%s/my-modules/MyApp', $this->dir->url());

        $mkdir = $this->getFunctionMock('Zend\Expressive\Tooling\Module', 'mkdir');
        $mkdir->expects($this->at(0))
            ->with($baseModulePath)
            ->willReturn(true);

        $mkdir->expects($this->at(1))
            ->with(sprintf('%s/src', $baseModulePath))
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Module source directory "%s/src" cannot be created',
            $baseModulePath
        ));
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testErrorsWhenCannotCreateModuleTemplatesDirectory()
    {
        $baseModulePath = sprintf('%s/my-modules/MyApp', $this->dir->url());

        $mkdir = $this->getFunctionMock('Zend\Expressive\Tooling\Module', 'mkdir');
        $mkdir->expects($this->at(0))
            ->with($baseModulePath)
            ->willReturn(true);

        $mkdir->expects($this->at(1))
            ->with(sprintf('%s/src', $baseModulePath))
            ->willReturn(true);

        $mkdir->expects($this->at(2))
            ->with(sprintf('%s/templates', $baseModulePath))
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Module templates directory "%s/templates" cannot be created',
            $baseModulePath
        ));
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testCreatesConfigProvider()
    {
        $configProvider = vfsStream::url('project/my-modules/MyApp/src/ConfigProvider.php');
        $this->assertEquals(
            sprintf('Created module MyApp in %s/MyApp', $this->modulesDir->url()),
            $this->command->process('MyApp', $this->modulesPath, $this->projectDir)
        );
        $this->assertFileExists($configProvider);
        $configProviderContent = file_get_contents($configProvider);
        $this->assertSame(1, preg_match('/\bnamespace MyApp\b/', $configProviderContent));
        $this->assertSame(1, preg_match('/\bclass ConfigProvider\b/', $configProviderContent));
        $command = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'MyApp', 'myapp');
        $this->assertSame($expectedContent, $configProviderContent);
    }

    public function testCreateModuleWithNamespace()
    {
        $configProvider = vfsStream::url('project/my-modules/MyNamespace/MyModule/src/ConfigProvider.php');
        $this->assertEquals(
            sprintf('Created module MyNamespace\\MyModule in %s/MyNamespace/MyModule', $this->modulesDir->url()),
            $this->command->process('MyNamespace\\MyModule', $this->modulesPath, $this->projectDir)
        );
        $this->assertFileExists($configProvider);
        $configProviderContent = file_get_contents($configProvider);
        $this->assertSame(1, preg_match('/\bnamespace MyNamespace\\\\MyModule\b/', $configProviderContent));
        $this->assertSame(1, preg_match('/\bclass ConfigProvider\b/', $configProviderContent));
        $command = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'MyNamespace\\MyModule', 'mynamespace-mymodule');
        $this->assertSame($expectedContent, $configProviderContent);
    }
}
