<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module\Command;

abstract class AbstractCommand
{
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $moduleName;

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
     * @param string $moduleName
     * @param string $composer
     * @param string $modulesPath
     */
    public function __construct($projectDir, $moduleName, $composer, $modulesPath)
    {
        $this->projectDir = $projectDir;
        $this->moduleName = $moduleName;
        $this->composer = $composer;
        $this->modulesPath = $modulesPath;
    }

    /**
     * Processes the command.
     *
     * @return void
     */
    abstract public function process();
}
