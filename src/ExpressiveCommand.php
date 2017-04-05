<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling;

use Zend\Stdlib\ConsoleHelper;

class ExpressiveCommand
{
    const DEFAULT_COMMAND_NAME = 'expressive';

    public $projectDir = '.';

    /**
     * @var string
     */
    private $command;

    /**
     * @var ConsoleHelper
     */
    private $console;

    /**
     * @var string[]
     */
    private $helpArgs = ['--help', '-h', 'help'];

    /**
     * @var string[]
     */
    private $knownCommands = [
        'create-middleware'         => CreateMiddleware\Command::class,
        'migrate-original-messages' => MigrateOriginalMessageCalls\Command::class,
        'module'                    => Module\Command::class,
        'pipeline-from-config'      => GenerateProgrammaticPipelineFromConfig\Command::class,
        'scan-for-error-middleware' => ScanForErrorMiddleware\Command::class,
    ];

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
            $help = new ExpressiveHelp($this->command, $this->console);
            $help(STDOUT);
            return 0;
        }

        $commandRequiringHelp = $this->discoverCommandHelpRequest($args);
        if (false !== $commandRequiringHelp) {
            return $this->dispatchCommand($commandRequiringHelp, ['help']);
        }

        try {
            list($command, $commandArgs) = $this->discoverCommand($args);
        } catch (InvalidCommandException $e) {
            $this->console->writeLine('<error>Invalid command</error>', true, STDERR);
            $help = new ExpressiveHelp($this->command, $this->console);
            $help(STDERR);
            return 1;
        }

        return $this->dispatchCommand($command, $commandArgs);
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

        if (in_array($arg, $this->helpArgs, true) && empty($args)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $args
     * @return false|string String command name, if help request discovered
     */
    private function discoverCommandHelpRequest(array $args)
    {
        if (2 > count($args)) {
            return false;
        }

        $first = array_shift($args);
        $second = array_shift($args);

        if (in_array($first, array_keys($this->knownCommands), true)
            && in_array($second, $this->helpArgs, true)
        ) {
            return $first;
        }

        if (in_array($second, array_keys($this->knownCommands), true)
            && in_array($first, $this->helpArgs, true)
        ) {
            return $second;
        }

        return false;
    }

    /**
     * @param array $args
     * @return array [command, array-of-arguments]
     * @throws InvalidCommandException
     */
    private function discoverCommand(array $args)
    {
        $command = array_shift($args);

        if (! in_array($command, array_keys($this->knownCommands), true)) {
            throw new InvalidCommandException();
        }

        return [$command, $args];
    }

    /**
     * @param string $command
     * @param array $args
     */
    private function dispatchCommand($command, array $args)
    {
        $class = $this->knownCommands[$command];

        $exec = new $class('expressive ' . $command, $this->console);
        $exec->projectDir = $this->projectDir;

        return $exec->process($args);
    }
}
