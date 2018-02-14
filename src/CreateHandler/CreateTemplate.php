<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use ReflectionClass;
use Zend\Expressive\Plates\PlatesRenderer;
use Zend\Expressive\Template\TemplateRendererInterface;
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
        $templateNamespace = $this->getTemplateNamespaceFromClass($handler);
        return $this->forHandlerUsingTemplateNamespace($handler, $templateNamespace);
    }

    public function forHandlerUsingTemplateNamespace(string $handler, string $templateNamespace) : Template
    {
        $config = $this->getConfig($this->projectPath);
        $rendererType = $this->resolveRendererType($config);
        $handlerPath = $this->getHandlerPath($handler);

        $templatePath = $this->getTemplatePathForNamespaceFromConfig($templateNamespace, $config)
            ?: $this->getTemplatePathForNamespaceBasedOnHandlerPath(
                $this->getNamespace($handler),
                $templateNamespace,
                $handlerPath
            );
        $templatePath = sprintf('%s/%s', $this->projectPath, $templatePath);

        if (! is_dir($templatePath)) {
            mkdir($templatePath, 0777, true);
        }

        $templateName = $this->getTemplateNameFromClass($handler);
        $templateFile = sprintf(
            '%s/%s.%s',
            $templatePath,
            $templateName,
            $this->getTemplateSuffixFromConfig($rendererType, $config)
        );

        file_put_contents($templateFile, sprintf('Template for %s', $handler));

        return new Template(
            $templateFile,
            sprintf('%s::%s', $templateNamespace, $templateName)
        );
    }

    private function resolveRendererType(array $config) : string
    {
        if (! isset($config['dependencies']['aliases'][TemplateRendererInterface::class])) {
            throw UnresolvableRendererException::dueToMissingAlias();
        }

        $type = $config['dependencies']['aliases'][TemplateRendererInterface::class];
        if (! in_array($type, self::KNOWN_RENDERERS, true)) {
            throw UnresolvableRendererException::dueToUnknownType($type);
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
     * @return null|string Returns null if on template path configuration
     *     exists for the namespace.
     * @throws TemplatePathResolutionException if configuration has more than
     *     one path defined for the namespace.
     */
    private function getTemplatePathForNamespaceFromConfig(string $templateNamespace, array $config) : ?string
    {
        if (! isset($config['templates']['paths'][$templateNamespace])) {
            return null;
        }

        $paths = $config['templates']['paths'][$templateNamespace];
        if (count($paths) !== 1) {
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
            return sprintf('src/%s/templates', $namespace);
        }
        return sprintf('templates/%s', $templateNamespace);
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
