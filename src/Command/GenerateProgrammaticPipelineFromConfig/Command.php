<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Command\GenerateProgrammaticPipelineFromConfig;

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
    private $helper;

    /**
     * @param ConsoleHelper $helper
     */
    public function __construct($command = self::DEFAULT_COMMAND_NAME, ConsoleHelper $helper = null)
    {
        $this->command = (string) $command;
        $this->helper = $helper ?: new ConsoleHelper();
    }

    /**
     * @param array $args
     * @return int
     */
    public function process(array $args)
    {
        if ($this->isHelpRequest($args)) {
            (new Help($this->command, $this->helper))(STDOUT);
            return 0;
        }

        if (! $this->isGenerateRequest($args)) {
            $this->helper->writeLine('<error>Unknown command</error>', true, STDERR);

            (new Help($this->command, $this->helper))(STDERR);
            return 1;
        }

        try {
            $generator = new Generator();
            $generator->projectDir = $this->projectDir;
            $generator->process($this->locateConfigFile($args));
        } catch (GeneratorException $e) {
            $this->helper->writeLine('<error>Error during generation:</error>', true, STDERR);
            $this->helper->writeLine(sprintf('  <error>%s</error>', $e->getMessage()), true, STDERR);
            return 1;
        }

        $this->helper->writeLine('<info>Success!</info>');
        $this->helper->writeLine(sprintf(
            '<info>- Created %s, enabling programmatic pipelines</info>',
            Generator::PATH_CONFIG
        ));
        $this->helper->writeLine(sprintf(
            '<info>- Created %s, defining the pipeline</info>',
            Generator::PATH_PIPELINE
        ));
        $this->helper->writeLine(sprintf(
            '<info>- Created %s, defining the routes</info>',
            Generator::PATH_ROUTES
        ));
        $this->helper->writeLine(sprintf(
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
