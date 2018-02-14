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
use Zend\Expressive\Plates\PlatesRenderer;
use Zend\Expressive\Twig\TwigRenderer;
use Zend\Expressive\ZendView\ZendViewRenderer;
use Zend\Expressive\Tooling\CreateHandler\CreateTemplate;
use Zend\Expressive\Tooling\CreateHandler\TemplatePathResolutionException;
use Zend\Expressive\Tooling\CreateHandler\UndetectableNamespaceException;
use Zend\Expressive\Tooling\CreateHandler\UnresolvableRendererException;

/**
 * @runTestsInSeparateProcesses
 */
class CreateTemplateTest extends TestCase
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

    public function updateConfigContents(string ...$replacements)
    {
        $configFile = $this->projectRoot . '/config/config.php';
        $contents = file_get_contents($configFile);
        $contents = vsprintf($contents, $replacements);
        file_put_contents($configFile, $contents);
    }

    public function rendererTypes() : array
    {
        return [
            PlatesRenderer::class   => [PlatesRenderer::class, 'phtml'],
            TwigRenderer::class     => [TwigRenderer::class, 'html.twig'],
            ZendViewRenderer::class => [ZendViewRenderer::class, 'phtml'],
        ];
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInExpectedLocationAndWithExpectedSuffixForFlatHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        require $this->projectRoot . '/src/Test/TestHandler.php';
        $this->updateConfigContents($rendererType, $extension);

        $generator = new CreateTemplate($this->projectRoot);
        $path = $generator->forHandler('Test\TestHandler');
        $this->assertRegexp('#templates/test/test\.' . $extension . '$#', $path);
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInExpectedLocationAndWithExpectedSuffixForModuleHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        $this->updateConfigContents($rendererType, $extension);

        $generator = new CreateTemplate($this->projectRoot);
        $path = $generator->forHandler('Test\TestHandler');
        $this->assertRegexp('#src/Test/templates/test\.' . $extension . '$#', $path);
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInDefaultLocationWhenNoTemplatesConfigPresentForFlatHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-path', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $path = $generator->forHandler('Test\TestHandler');
        $this->assertRegexp('#templates/test/test\.' . $extension . '$#', $path);
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInDefaultLocationWhenNoTemplatesConfigPresentForModuleHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-path', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $path = $generator->forHandler('Test\TestHandler');
        $this->assertRegexp('#src/Test/templates/test\.' . $extension . '$#', $path);
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileUsingConfiguredValuesForFlatHierarchy(
        string $rendererType
    ) {
        $extension = 'custom';
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($rendererType, $extension);

        $generator = new CreateTemplate($this->projectRoot);
        $path = $generator->forHandler('Test\TestHandler');
        $this->assertRegexp('#view/for-testing/test\.' . $extension . '$#', $path);
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileUsingConfiguredValuesForModuleHierarchy(
        string $rendererType
    ) {
        $extension = 'custom';
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($rendererType, $extension);

        $generator = new CreateTemplate($this->projectRoot);
        $path = $generator->forHandler('Test\TestHandler');
        $this->assertRegexp('#view/for-testing/test\.' . $extension . '$#', $path);
    }

    public function testGeneratingTemplateWhenRendererServiceNotFoundResultsInException()
    {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.missing-renderer', $this->projectRoot . '/config/config.php');

        $generator = new CreateTemplate($this->projectRoot);

        $this->expectException(UnresolvableRendererException::class);
        $this->expectExceptionMessage('inability to detect a service alias');
        $generator->forHandler('Test\TestHandler');
    }

    public function testGeneratingTemplateWhenRendererServiceIsNotInWhitelistResultsInException()
    {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.unrecognized-renderer', $this->projectRoot . '/config/config.php');

        $generator = new CreateTemplate($this->projectRoot);

        $this->expectException(UnresolvableRendererException::class);
        $this->expectExceptionMessage('unknown template renderer type');
        $generator->forHandler('Test\TestHandler');
    }

    public function testGeneratingTemplateWhenHandlerIsInUnrecognizedLocationResultsInException()
    {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        mkdir($this->projectRoot . '/library');
        rename($this->projectRoot . '/src/Test/TestHandler.php', $this->projectRoot . '/library/TestHandler.php');
        require $this->projectRoot . '/library/TestHandler.php';

        $generator = new CreateTemplate($this->projectRoot);

        $this->expectException(UndetectableNamespaceException::class);
        $this->expectExceptionMessage('library/TestHandler.php');
        $generator->forHandler('Test\TestHandler');
    }

    public function rendererTypesWithInvalidPathCounts() : iterable
    {
        foreach (['empty-paths', 'too-many-paths'] as $config) {
            foreach ($this->rendererTypes() as $key => $arguments) {
                array_push($arguments, sprintf('config.php.%s', $config));
                $name = sprintf('%s-%s', $key, $config);
                yield $name => $arguments;
            }
        }
    }

    /**
     * @dataProvider rendererTypesWithInvalidPathCounts
     */
    public function testRaisesExceptionWhenConfiguredPathCountIsInvalidForFlatHierarchy(
        string $rendererType,
        string $extension,
        string $configFile
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/' . $configFile, $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($rendererType, $extension);

        $generator = new CreateTemplate($this->projectRoot);

        $this->expectException(TemplatePathResolutionException::class);
        $generator->forHandler('Test\TestHandler');
    }

    /**
     * @dataProvider rendererTypesWithInvalidPathCounts
     */
    public function testRaisesExceptionWhenConfiguredPathCountIsInvalidForModuleHierarchy(
        string $rendererType,
        string $extension,
        string $configFile
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/' . $configFile, $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($rendererType, $extension);

        $generator = new CreateTemplate($this->projectRoot);

        $this->expectException(TemplatePathResolutionException::class);
        $generator->forHandler('Test\TestHandler');
    }
}
