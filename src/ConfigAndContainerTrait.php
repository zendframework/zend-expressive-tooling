<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling;

use ArrayObject;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function iterator_to_array;
use function sprintf;

trait ConfigAndContainerTrait
{
    /**
     * @var ContainerInterface
     */
    private $container;

    private function getContainer(string $projectPath) : ContainerInterface
    {
        if ($this->container) {
            return $this->container;
        }

        $containerPath = sprintf('%s/config/container.php', $projectPath);
        $this->container = require $containerPath;
        return $this->container;
    }

    /**
     * Retrieve project configuration.
     */
    private function getConfig(string $projectPath) : array
    {
        $config = $this->getContainer($projectPath)->get('config');

        if (is_array($config)) {
            return $config;
        }

        if (! $config instanceof ArrayObject) {
            $error = sprintf(
                '"config" service must be an array or instance of ArrayObject, got %s',
                is_object($config) ? get_class($config) : gettype($config)
            );
            throw new RuntimeException($error);
        }

        return iterator_to_array($config);
    }
}
