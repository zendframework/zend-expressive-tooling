<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateMiddleware;

/**
 * Create middleware
 *
 * Creates a middleware class file for a given class in a given project root.
 */
class CreateMiddleware
{
    /**
     * @var string Template for middleware class.
     */
    public const CLASS_SKELETON = <<< 'EOS'
<?php

declare(strict_types=1);

namespace %namespace%;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class %class% implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        // $response = $handler->handle($request);
    }
}

EOS;

    /**
     * @throws CreateMiddlewareException
     */
    public function process(
        string $class,
        string $projectRoot = null,
        string $classSkeleton = self::CLASS_SKELETON
    ) : string {
        $projectRoot = $projectRoot ?: getcwd();

        $path = $this->getClassPath($class, $projectRoot);

        list($namespace, $class) = $this->getNamespaceAndClass($class);

        $content = str_replace(
            ['%namespace%', '%class%'],
            [$namespace, $class],
            $classSkeleton
        );

        if (is_file($path)) {
            throw CreateMiddlewareException::classExists($path, $class);
        }

        file_put_contents($path, $content);
        return $path;
    }

    /**
     * @throws CreateMiddlewareException
     */
    private function getClassPath(string $class, string $projectRoot) : string
    {
        $autoloaders = $this->getComposerAutoloaders($projectRoot);
        list($namespace, $path) = $this->discoverNamespaceAndPath($class, $autoloaders);

        // Absolute path to namespace
        $path = implode([$projectRoot, DIRECTORY_SEPARATOR, $path]);

        $parts = explode('\\', $class);
        $className = array_pop($parts);

        // Create absolute path to subnamespace, if required
        $nsParts = explode('\\', trim($namespace, '\\'));
        $subNsParts = array_slice($parts, count($nsParts));

        if (0 < count($subNsParts)) {
            $subNsPath = implode(DIRECTORY_SEPARATOR, $subNsParts);
            $path = implode([$path, DIRECTORY_SEPARATOR, $subNsPath]);
        }

        // Create path if it does not exist
        if (! is_dir($path)) {
            if (false === mkdir($path, 0755, true)) {
                throw CreateMiddlewareException::unableToCreatePath($path, $class);
            }
        }

        return $path . DIRECTORY_SEPARATOR . $className . '.php';
    }

    /**
     * @return array Associative array of namespace/path pairs
     * @throws CreateMiddlewareException
     */
    private function getComposerAutoloaders(string $projectRoot) : array
    {
        $composerPath = sprintf('%s/composer.json', $projectRoot);
        if (! file_exists($composerPath)) {
            throw CreateMiddlewareException::missingComposerJson();
        }

        $composer = json_decode(file_get_contents($composerPath), true);

        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw CreateMiddlewareException::invalidComposerJson(json_last_error_msg());
        }

        if (! isset($composer['autoload']['psr-4'])) {
            throw CreateMiddlewareException::missingComposerAutoloaders();
        }

        if (! is_array($composer['autoload']['psr-4'])) {
            throw CreateMiddlewareException::missingComposerAutoloaders();
        }

        return $composer['autoload']['psr-4'];
    }

    /**
     * @return array [namespace, path]
     * @throws CreateMiddlewareException
     */
    private function discoverNamespaceAndPath(string $class, array $autoloaders) : array
    {
        foreach ($autoloaders as $namespace => $path) {
            if (0 === strpos($class, $namespace)) {
                $path = trim(
                    str_replace(
                        ['/', '\\'],
                        DIRECTORY_SEPARATOR,
                        $path
                    ),
                    DIRECTORY_SEPARATOR
                );
                return [$namespace, $path];
            }
        }

        throw CreateMiddlewareException::autoloaderNotFound($class);
    }

    /**
     * @return array [namespace, class]
     */
    private function getNamespaceAndClass(string $class) : array
    {
        $parts = explode('\\', $class);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);
        return [$namespace, $className];
    }
}
