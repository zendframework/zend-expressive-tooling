<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\CreateHandler;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Zend\Expressive\Plates\PlatesRenderer;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Twig\TwigRenderer;
use Zend\Expressive\ZendView\ZendViewRenderer;
use Zend\Expressive\Tooling\CreateHandler\CreateTemplate;
use Zend\Expressive\Tooling\CreateHandler\TemplatePathResolutionException;
use Zend\Expressive\Tooling\CreateHandler\UndetectableNamespaceException;
use Zend\Expressive\Tooling\CreateHandler\UnresolvableRendererException;
use Zend\Expressive\Tooling\CreateHandler\UnknownTemplateSuffixException;
use Zend\Expressive\Tooling\CreateHandler\Template;

/**
 * @runTestsInSeparateProcesses
 */
class CreateTemplateTest extends TestCase
{
    private const COMMON_FILES = [
        '/TestAsset/common/PlatesRenderer.php'   => '/src/PlatesRenderer.php',
        '/TestAsset/common/TwigRenderer.php'     => '/src/TwigRenderer.php',
        '/TestAsset/common/ZendViewRenderer.php' => '/src/ZendViewRenderer.php',
    ];

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $projectRoot;

    /** @var PlatesRenderer|TwigRenderer|ZendViewRenderer */
    private $renderer;

    public function setUp()
    {
        $this->dir = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function prepareCommonAssets()
    {
        foreach (self::COMMON_FILES as $source => $target) {
            copy(__DIR__ . $source, $this->projectRoot . $target);
        }
    }

    public function injectConfigInContainer()
    {
        $configFile = $this->projectRoot . '/config/config.php';
        $config = include $configFile;
        $this->container->get('config')->willReturn($config);
    }

    public function injectRendererInContainer(string $renderer)
    {
        $className = substr($renderer, strrpos($renderer, '\\') + 1);
        $sourceFile = sprintf('%s/src/%s.php', $this->projectRoot, $className);
        require $sourceFile;
        $this->renderer = new $renderer();
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($this->renderer);
    }

    public function injectContainerInGenerator(CreateTemplate $generator)
    {
        $r = new ReflectionProperty($generator, 'container');
        $r->setAccessible(true);
        $r->setValue($generator, $this->container->reveal());
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
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->forHandler('Test\TestHandler');
        $this->assertSame($this->projectRoot . '/config/../templates/test/test.' . $extension, $template->getPath());
        $this->assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInExpectedLocationAndWithExpectedSuffixForModuleHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->forHandler('Test\TestHandler');
        $this->assertSame(
            $this->projectRoot . '/config/../src/Test/templates/test.' . $extension,
            $template->getPath()
        );
        $this->assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInDefaultLocationWhenNoTemplatesConfigPresentForFlatHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-path', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->forHandler('Test\TestHandler');
        $this->assertSame($this->projectRoot . '/templates/test/test.' . $extension, $template->getPath());
        $this->assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInDefaultLocationWhenNoTemplatesConfigPresentForModuleHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-path', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->forHandler('Test\TestHandler');
        $this->assertSame($this->projectRoot . '/src/Test/templates/test.' . $extension, $template->getPath());
        $this->assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileUsingConfiguredValuesForFlatHierarchy(
        string $rendererType
    ) {
        $extension = 'custom';
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->forHandler('Test\TestHandler');
        $this->assertSame($this->projectRoot . '/config/../view/for-testing/test.' . $extension, $template->getPath());
        $this->assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileUsingConfiguredValuesForModuleHierarchy(
        string $rendererType
    ) {
        $extension = 'custom';
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->forHandler('Test\TestHandler');
        $this->assertSame($this->projectRoot . '/config/../view/for-testing/test.' . $extension, $template->getPath());
        $this->assertSame('test::test', $template->getName());
    }

    public function testGeneratingTemplateWhenRendererServiceNotFoundResultsInException()
    {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.missing-renderer', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer();
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->get(TemplateRendererInterface::class)->shouldNotBeCalled();

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $this->expectException(UnresolvableRendererException::class);
        $this->expectExceptionMessage('inability to detect a service alias');
        $generator->forHandler('Test\TestHandler');
    }

    public function testGeneratingTemplateWhenRendererServiceIsNotInWhitelistResultsInException()
    {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy(
            $this->projectRoot . '/config/config.php.unrecognized-renderer',
            $this->projectRoot . '/config/config.php'
        );
        $this->injectConfigInContainer();
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($this);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $this->expectException(UnresolvableRendererException::class);
        $this->expectExceptionMessage('unknown template renderer type');
        $generator->forHandler('Test\TestHandler');
    }

    public function rendererTypesWithInvalidPathCounts() : iterable
    {
        foreach (['empty-paths'] as $config) {
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
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/' . $configFile, $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

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
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/' . $configFile, $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $this->expectException(TemplatePathResolutionException::class);
        $generator->forHandler('Test\TestHandler');
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testCanGenerateTemplateUsingProvidedNamespaceAndNameWhenConfigurationMatchesForFlatHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom-namespace', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->generateTemplate('Test\TestHandler', 'custom', 'also-custom');
        $this->assertSame(
            $this->projectRoot . '/config/../templates/custom/also-custom.' . $extension,
            $template->getPath()
        );
        $this->assertSame('custom::also-custom', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testCanGenerateTemplateUsingProvidedNamespaceAndNameWhenConfigurationMatchesForModuleHierarchy(
        string $rendererType,
        string $extension
    ) {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom-namespace', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->generateTemplate('Test\TestHandler', 'custom', 'also-custom');
        $this->assertSame(
            $this->projectRoot . '/config/../src/Custom/templates/also-custom.' . $extension,
            $template->getPath()
        );
        $this->assertSame('custom::also-custom', $template->getName());
    }

    public function testCanGenerateTemplateWithUnrecognizedRendererTypeIfTemplatSuffixIsProvided()
    {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-extension', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer();
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($this);

        $generator = new CreateTemplate($this->projectRoot);
        $this->injectContainerInGenerator($generator);

        $template = $generator->generateTemplate('Test\TestHandler', 'custom', 'also-custom', 'XHTML');
        $this->assertSame(
            $this->projectRoot . '/config/../src/Custom/templates/also-custom.XHTML',
            $template->getPath()
        );
        $this->assertSame('custom::also-custom', $template->getName());
    }
}
