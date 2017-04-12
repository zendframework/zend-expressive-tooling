<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

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
    const CLASS_SKELETON = <<< 'EOS'
<?php

namespace %namespace%;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class %class% implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // $response = $delegate->process($request);
    }
}
EOS;

    /**
     * @param string $class
     * @param string|null $projectRoot
     * @return string
     * @throws CreateMiddlewareException
     */
    public function process($class, $projectRoot = null)
    {
        $projectRoot = $projectRoot ?: getcwd();

        $path = $this->getClassPath($class, $projectRoot);

        list($namespace, $class) = $this->getNamespaceAndClass($class);

        $content = str_replace(
            ['%namespace%', '%class%'],
            [$namespace, $class],
            self::CLASS_SKELETON
        );

        file_put_contents($path, $content);
        return $path;
    }

    /**
     * @param string $class
     * @param string $projectRoot
     * @return string
     * @throws CreateMiddlewareException
     */
    private function getClassPath($class, $projectRoot)
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
     * @param string $projectRoot
     * @return array Associative array of namespace/path pairs
     * @throws CreateMiddlewareException
     */
    private function getComposerAutoloaders($projectRoot)
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
     * @param string $class
     * @param array $autoloaders
     * @return array [namespace, path]
     * @throws CreateMiddlewareException
     */
    private function discoverNamespaceAndPath($class, array $autoloaders)
    {
        $discoveredPath = false;
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
     * @param string $class
     * @return array [namespace, class]
     */
    private function getNamespaceAndClass($class)
    {
        $parts = explode('\\', $class);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);
        return [$namespace, $className];
    }
}
