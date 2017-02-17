<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module;

use Zend\Expressive\Tooling\Module\Command\AbstractCommand;
use Zend\Expressive\Tooling\Module\Exception;
use Zend\Stdlib\ConsoleHelper;

class Command
{
    const DEFAULT_COMMAND_NAME = 'expressive-module';

    /**
     * @var string
     */
    public $projectDir = '.';

    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $helpArgs = ['--help', '-h', 'help'];

    /**
     * @var array
     */
    private $commands = ['create', 'register', 'deregister'];

    /**
     * @var ConsoleHelper
     */
    private $console;

    /**
     * @var string
     */
    private $module;

    /**
     * @var string
     */
    private $composer = 'composer';

    /**
     * @var string
     */
    private $modulesPath = 'src';

    /**
     * @param string $command Script that is invoking the command.
     * @param ConsoleHelper $console
     */
    public function __construct($command = self::DEFAULT_COMMAND_NAME, ConsoleHelper $console = null)
    {
        $this->command = (string) $command;
        $this->console = $console ?: new ConsoleHelper();
    }

    /**
     * @param array $args
     * @return int
     */
    public function process(array $args)
    {
        if ($this->isHelpRequest($args)) {
            return $this->showHelp();
        }

        $command = $this->getCommand($args);
        if ($command === false) {
            $this->console->writeErrorMessage('Unknown command');

            return $this->showHelp(STDERR);
        }

        try {
            $this->parseArguments($args);
        } catch (Exception\InvalidArgumentException $ex) {
            $this->console->writeErrorMessage($ex->getMessage());

            return $this->showHelp(STDERR);
        }

        try {
            /** @var AbstractCommand $instance */
            $instance = new $command(
                $this->console,
                $this->projectDir,
                $this->module,
                $this->composer,
                $this->modulesPath
            );
            $instance->process();
        } catch (Exception\RuntimeException $ex) {
            $this->console->writeLine('<error>Error during execution:</error>', true, STDERR);
            $this->console->writeLine(sprintf('  <error>%s</error>', $ex->getMessage()), true, STDERR);
            return 1;
        }

        return 0;
    }

    /**
     * Outputs help message to the resource.
     *
     * @param resource $resource
     * @return int
     */
    private function showHelp($resource = STDOUT)
    {
        $help = new Help($this->command, $this->console);
        $help($resource);

        return $resource === STDERR ? 1 : 0;
    }

    /**
     * Is this a help request?
     *
     * @param array $args
     * @return bool
     */
    private function isHelpRequest(array $args)
    {
        $numArgs = count($args);
        if (0 === $numArgs) {
            return true;
        }

        $arg = array_shift($args);

        if (in_array($arg, $this->helpArgs, true)) {
            return true;
        }

        if (empty($args)) {
            return false;
        }

        $arg = array_shift($args);

        return in_array($arg, $this->helpArgs, true);
    }

    /**
     * Returns one of available command name or false otherwise.
     *
     * @param array $args
     * @return string|false
     */
    private function getCommand(array $args)
    {
        if (! $args) {
            return false;
        }

        $arg = array_shift($args);
        if (in_array($arg, $this->commands, true)) {
            return __NAMESPACE__ . '\\Command\\' . ucfirst($arg);
        }

        return false;
    }

    /**
     * Parses provided arguments and checks them. If arguments are not provided checks default values.
     *
     * @param array $args
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    private function parseArguments(array $args)
    {
        // Remove command argument
        array_shift($args);

        // Get module argument (always expected in last position)
        $this->module = array_pop($args);
        if (! $this->module) {
            throw new Exception\InvalidArgumentException('Invalid module name');
        }

        // Parse arguments
        $args = array_values($args);
        $count = count($args);

        if (0 !== $count % 2) {
            throw new Exception\InvalidArgumentException('Invalid arguments');
        }

        for ($i = 0; $i < $count; $i += 2) {
            switch ($args[$i]) {
                case '--composer':
                    // fall-through
                case '-c':
                    $this->composer = $args[$i + 1];
                    break;

                case '--modules-path':
                    // fall-through
                case '-p':
                    $this->modulesPath = preg_replace('/^\.\//', '', str_replace('\\', '/', $args[$i + 1]));
                    break;

                default:
                    throw new Exception\InvalidArgumentException(sprintf('Unknown argument "%s" provided', $args[$i]));
            }
        }

        if (! is_dir(sprintf('%s/%s', $this->projectDir, $this->modulesPath))) {
            throw new Exception\InvalidArgumentException(
                'Provided path to the modules directory does not exist or is not a directory'
            );
        }

        $output = [];
        $returnVar = null;
        exec($this->composer, $output, $returnVar);

        // ! is_executable($this->composer)
        if ($returnVar !== 0) {
            throw new Exception\InvalidArgumentException(
                'Provided composer binary does not exist or is not executable'
            );
        }
    }
}
