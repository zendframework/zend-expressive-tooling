<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use function preg_replace;
use function strtolower;
use function strpos;
use function strrpos;
use function substr;

trait TemplateResolutionTrait
{
    /**
     * Normalizes identifier to lowercase, dash-separated words.
     */
    private function normalizeTemplateIdentifier(string $identifier) : string
    {
        $pattern     = ['#(?<=(?:\p{Lu}))(\p{Lu}\p{Ll})#', '#(?<=(?:\p{Ll}|\p{Nd}))(\p{Lu})#'];
        $replacement = ['-\1', '-\1'];
        $identifier  = preg_replace($pattern, $replacement, $identifier);
        return strtolower($identifier);
    }

    /**
     * Returns the top-level namespace for the given class.
     */
    private function getNamespace(string $class) : string
    {
        return substr($class, 0, strpos($class, '\\'));
    }

    /**
     * Retrieves the namespace for the class using getNamespace, passes
     * the result to normalizeTemplateIdentifier(), and returns the result.
     */
    private function getTemplateNamespaceFromClass(string $class) : string
    {
        return $this->normalizeTemplateIdentifier($this->getNamespace($class));
    }

    /**
     * Returns the unqualified class name (class minus namespace).
     */
    private function getClassName(string $class) : string
    {
        return substr($class, strrpos($class, '\\') + 1);
    }

    /**
     * Passes the $class to getClassName(), strips any "Action" or "Handler"
     * or "Middleware" suffixes, passes it to normalizeTemplateIdentifier(),
     * and returns the result.
     */
    private function getTemplateNameFromClass(string $class) : string
    {
        return $this->normalizeTemplateIdentifier(
            preg_replace(
                '#(Action|Handler|Middleware)$#',
                '',
                $this->getClassName($class)
            )
        );
    }

    /**
     * Retrieve project configuration.
     */
    private function getConfig(string $projectPath) : array
    {
        $projectPath = rtrim($projectPath, '/\\');
        $configFile = $projectPath . '/config/config.php';
        if (! file_exists($configFile)) {
            return [];
        }
        return include $configFile;
    }
}
