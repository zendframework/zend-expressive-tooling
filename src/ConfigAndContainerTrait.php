<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling;

use Psr\Container\ContainerInterface;

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
        return $this->getContainer($projectPath)->get('config');
    }
}
