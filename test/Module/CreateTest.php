<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2019 Zend Technologies USA Inc. (https://www.zend.com)
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

    protected function setUp() : void
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
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'MyApp', 'my-app');
        $this->assertSame($expectedContent, $configProviderContent);
    }

    public function testModuleTemplatePathNameWithNumber()
    {
        $this->command->process('My2App', $this->modulesPath, $this->projectDir);
        $configProvider = vfsStream::url('project/my-modules/My2App/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $command = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'My2App', 'my2-app');
        $this->assertSame($expectedContent, $configProviderContent);
    }

    public function testModuleTemplatePathNameWithSequentialUppercase()
    {
        $this->command->process('THEApp', $this->modulesPath, $this->projectDir);
        $configProvider = vfsStream::url('project/my-modules/THEApp/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $command = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'THEApp', 'the-app');
        $this->assertSame($expectedContent, $configProviderContent);
    }
}
