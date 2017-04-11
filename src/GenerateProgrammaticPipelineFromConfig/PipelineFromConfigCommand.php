<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PipelineFromConfigCommand extends Command implements Constants
{
    const DEFAULT_CONFIG_FILE = '/config/config.php';

    const HELP = <<< 'EOT'
Reads existing configuration from the --config-file, and uses that
information to generate a programmatic middleware pipeline and routing
table for your application:

- The pipeline is written to config/pipeline.php.
- The routing rules are written to config/routes.php.
- A new configuration file, config/autoload/programmatic-pipeline.global.php,
  is created with configuration to enable the programmatic pipeline and
  new error handling mechanisms.
- The script updates public/index.php to require the pipeline and
  routing configuration files prior to running the application.
EOT;

    const HELP_OPT_CONFIG_FILE = <<< 'EOT'
Specify a path to the configuration file; defaults to 'config/config.php'.
The file is expected to return a PHP array value containing all
configuration.
EOT;

    /**
     * Configure the console command.
     */
    protected function configure()
    {
        $this->setDescription('Generate a programmatic pipeline and routes from configuration.');
        $this->setHelp(self::HELP);
        $this->addOption('config-file', 'c', InputOption::VALUE_REQUIRED, self::HELP_OPT_CONFIG_FILE);
    }

    /**
     * Execute console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            '<info>Generating programmatic pipeline for an existing Expressive application...</info>'
        );

        $generator = new Generator($output);
        $generator->process($this->locateConfigFile($input));

        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf(
            '<info>- Created %s, enabling programmatic pipelines</info>',
            self::PATH_CONFIG
        ));
        $output->writeln(sprintf(
            '<info>- Created %s, defining the pipeline</info>',
            self::PATH_PIPELINE
        ));
        $output->writeln(sprintf(
            '<info>- Created %s, defining the routes</info>',
            self::PATH_ROUTES
        ));
        $output->writeln(sprintf(
            '<info>- Updated %s to include %s and %s before running the application</info>',
            self::PATH_APPLICATION,
            self::PATH_PIPELINE,
            self::PATH_ROUTES
        ));

        return 0;
    }

    /**
     * Determine the config file location based on the arguments provided.
     *
     * If no --config-file switch or associated value, returns the default
     * config file location; otherwise, returns the provided value.
     *
     * @param InputInterface $input
     * @return string
     */
    private function locateConfigFile(InputInterface $input)
    {
        $configFile = $input->getOption('config-file') ?: self::DEFAULT_CONFIG_FILE;
        $configFile = '/' . ltrim($configFile, '/');
        return getcwd() . $configFile;
    }
}
