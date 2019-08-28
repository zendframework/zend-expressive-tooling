<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Traversable;

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
     * @var string $projectPath Project directory path
     * @throws RuntimeException If $config is not an instance of Traversable
     */
    private function getConfig(string $projectPath) : array
    {
        $config = $this->getContainer($projectPath)->get('config');

        if (is_array($config)) {
            return $config;
        }

        if (! $config instanceof Traversable) {
            throw new RuntimeException(
                sprintf(
                    '"config" service must be an array or instance of ArrayObject or Traversable, got %s',
                    is_object($config) ? get_class($config) : gettype($config)
                )
            );
        }

        return iterator_to_array($config);
    }
}
