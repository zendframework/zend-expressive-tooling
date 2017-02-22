<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module\Command;

use Zend\Expressive\Tooling\Module\Exception;

abstract class AbstractCommand
{
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $composer;

    /**
     * @var string
     */
    protected $modulesPath;

    /**
     * @param string $projectDir
     * @param string $modulesPath
     * @param string $composer
     */
    public function __construct($projectDir, $modulesPath, $composer)
    {
        $this->projectDir = $projectDir;
        $this->composer = $composer;
        $this->modulesPath = $modulesPath;
    }

    /**
     * Processes the command.
     *
     * @param string $moduleName
     * @return bool
     * @throws Exception\RuntimeException
     */
    abstract public function process($moduleName);
}
