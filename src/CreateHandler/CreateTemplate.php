<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use ReflectionClass;
use Zend\Expressive\Plates\PlatesRenderer;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Tooling\TemplateResolutionTrait;
use Zend\Expressive\Twig\TwigRenderer;
use Zend\Expressive\ZendView\ZendViewRenderer;

class CreateTemplate
{
    use TemplateResolutionTrait;

    /**
     * Array of renderers we can generate templates for.
     *
     * @var string[]
     */
    private const KNOWN_RENDERERS = [
        PlatesRenderer::class,
        TwigRenderer::class,
        ZendViewRenderer::class,
    ];

    /**
     * Root directory of project; used to determine if handler path indicates a
     * module.
     *
     * @var string
     */
    private $projectPath;

    public function __construct(string $projectPath = null)
    {
        $this->projectPath = $projectPath ?: realpath(getcwd());
    }

    public function forHandler(string $handler) : Template
    {
        return $this->generateTemplate(
            $handler,
            $this->getTemplateNamespaceFromClass($handler),
            $this->getTemplateNameFromClass($handler)
        );
    }

    public function generateTemplate(
        string $handler,
        string $templateNamespace,
        string $templateName,
        string $templateSuffix = null
    ) : Template {
        $config = $this->getConfig($this->projectPath);
        $rendererType = $this->resolveRendererType($templateSuffix);
        $handlerPath = $this->getHandlerPath($handler);

        $templatePath = $this->getTemplatePathForNamespaceFromConfig($templateNamespace, $config)
            ?: $this->getTemplatePathForNamespaceBasedOnHandlerPath(
                $this->getNamespace($handler),
                $templateNamespace,
                $handlerPath
            );

        if (! is_dir($templatePath)) {
            mkdir($templatePath, 0777, true);
        }

        $templateFile = sprintf(
            '%s/%s.%s',
            $templatePath,
            $templateName,
            $templateSuffix ?: $this->getTemplateSuffixFromConfig($rendererType, $config)
        );

        file_put_contents($templateFile, sprintf('Template for %s', $handler));

        return new Template(
            $templateFile,
            sprintf('%s::%s', $templateNamespace, $templateName)
        );
    }

    private function resolveRendererType(?string $templateSuffix) : string
    {
        $container = $this->getContainer($this->projectPath);
        if (! $this->containerDefinesRendererService($container)) {
            throw UnresolvableRendererException::dueToMissingAlias();
        }

        $type = $this->getRendererServiceTypeFromContainer($container);

        // We only need to test for a known renderer type if there is no
        // template suffix available.
        if (null === $templateSuffix) {
            if (! in_array($type, self::KNOWN_RENDERERS, true)) {
                throw UnresolvableRendererException::dueToUnknownType($type);
            }
        }

        return $type;
    }

    private function getHandlerPath(string $handler) : string
    {
        $r = new ReflectionClass($handler);
        $path = $r->getFileName();
        $path = preg_replace('#^' . preg_quote($this->projectPath) . '#', '', $path);
        $path = ltrim($path, '/\\');
        return rtrim($path, '/\\');
    }

    /**
     * @todo If more than one template path exists, we should likely prompt the
     *     user for which one to which to install the template.
     * @return null|string Returns null if no template path configuration
     *     exists for the namespace.
     * @throws TemplatePathResolutionException if configuration has zero paths
     *     defined for the namespace.
     */
    private function getTemplatePathForNamespaceFromConfig(string $templateNamespace, array $config) : ?string
    {
        if (! isset($config['templates']['paths'][$templateNamespace])) {
            return null;
        }

        $paths = $config['templates']['paths'][$templateNamespace];
        if (count($paths) === 0) {
            throw TemplatePathResolutionException::forNamespace($templateNamespace);
        }
        $path = array_shift($paths);
        return rtrim($path, '/\\');
    }

    private function getTemplatePathForNamespaceBasedOnHandlerPath(
        string $namespace,
        string $templateNamespace,
        string $path
    ) : string {
        if ($this->pathRepresentsModule($path, $namespace)) {
            return sprintf('%s/src/%s/templates', $this->projectPath, $namespace);
        }
        return sprintf('%s/templates/%s', $this->projectPath, $templateNamespace);
    }

    private function pathRepresentsModule(string $path, string $namespace) : bool
    {
        $regex = sprintf('#^src/%s/(?P<isModule>src/)?#', $namespace);
        $matches = [];
        preg_match($regex, $path, $matches);
        return isset($matches['isModule']);
    }

    private function getTemplateSuffixFromConfig(string $type, array $config) : string
    {
        if (! isset($config['templates']['extension'])) {
            return $this->getDefaultTemplateSuffix($type);
        }
        return $config['templates']['extension'];
    }

    /**
     * This method will only be triggered if we know we have a known
     * renderer type.
     */
    private function getDefaultTemplateSuffix(string $type) : string
    {
        switch ($type) {
            case TwigRenderer::class:
                return 'html.twig';
            case PlatesRenderer::class:
                // fall-through
            case ZendViewRenderer::class:
                // fall-through
            default:
                return 'phtml';
        }
    }
}
