<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig;

use Zend\Stdlib\ConsoleHelper;

class Command
{
    const DEFAULT_COMMAND_NAME = 'expressive-pipeline-from-config';

    const DEFAULT_CONFIG_FILE = '/config/config.php';

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
     * @var ConsoleHelper
     */
    private $console;

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
            $help = new Help($this->command, $this->console);
            $help(STDOUT);
            return 0;
        }

        if (! $this->isGenerateRequest($args)) {
            $this->console->writeLine('<error>Unknown command</error>', true, STDERR);

            $help = new Help($this->command, $this->console);
            $help(STDERR);
            return 1;
        }

        $this->console->writeLine(
            '<info>Generating programmatic pipeline for an existing Expressive application...</info>'
        );

        try {
            $generator = new Generator();
            $generator->projectDir = $this->projectDir;
            $generator->process($this->locateConfigFile($args));
        } catch (GeneratorException $e) {
            $this->console->writeLine('<error>Error during generation:</error>', true, STDERR);
            $this->console->writeLine(sprintf('  <error>%s</error>', $e->getMessage()), true, STDERR);
            return 1;
        }

        $this->console->writeLine('<info>Success!</info>');
        $this->console->writeLine(sprintf(
            '<info>- Created %s, enabling programmatic pipelines</info>',
            Generator::PATH_CONFIG
        ));
        $this->console->writeLine(sprintf(
            '<info>- Created %s, defining the pipeline</info>',
            Generator::PATH_PIPELINE
        ));
        $this->console->writeLine(sprintf(
            '<info>- Created %s, defining the routes</info>',
            Generator::PATH_ROUTES
        ));
        $this->console->writeLine(sprintf(
            '<info>- Updated %s to include %s and %s before running the application</info>',
            Generator::PATH_APPLICATION,
            Generator::PATH_PIPELINE,
            Generator::PATH_ROUTES
        ));

        return 0;
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
     * Is this a request to generate a pipeline?
     *
     * @param array $args
     * @return bool
     */
    private function isGenerateRequest(array $args)
    {
        if (0 === count($args)) {
            return false;
        }

        $arg = array_shift($args);

        return $arg === 'generate';
    }

    /**
     * Determine the config file location based on the arguments provided.
     *
     * If no --config-file switch or associated value, returns the default
     * config file location; otherwise, returns the provided value.
     *
     * @param array $args
     * @return string
     */
    private function locateConfigFile(array $args)
    {
        $args = array_values($args);

        if (3 > count($args)) {
            return $this->projectDir . self::DEFAULT_CONFIG_FILE;
        }

        if ('--config-file' !== $args[1]) {
            return $this->projectDir . self::DEFAULT_CONFIG_FILE;
        }

        return $args[2];
    }
}
