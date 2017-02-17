<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module\Command;

use Zend\Expressive\Tooling\Module\ComposerConsole;
use Zend\Stdlib\ConsoleHelper;

abstract class AbstractCommand
{
    /**
     * @var ConsoleHelper
     */
    protected $console;

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
     * @param ConsoleHelper $console
     * @param string $projectDir
     * @param string $moduleName
     * @param string $composer
     * @param string $modulesPath
     */
    public function __construct(ConsoleHelper $console, $projectDir, $moduleName, $composer, $modulesPath)
    {
        $this->console = $console;
        $this->projectDir = $projectDir;
        $this->moduleName = $moduleName;
        $this->composer = $composer;
        $this->modulesPath = $modulesPath;
    }

    /**
     * @return ComposerConsole
     */
    protected function getComposerConsole()
    {
        return new ComposerConsole($this->console);
    }

    /**
     * Processes the command.
     *
     * @return void
     */
    abstract public function process();
}
