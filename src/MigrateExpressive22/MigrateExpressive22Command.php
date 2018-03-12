<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\MigrateExpressive22;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateExpressive22Command extends Command
{
    const HELP = <<< 'EOT'
Migrate an Expressive application to version 2.2.

This command does the following:

- Adds entries for the zend-expressive and zend-expressive-router
  ConfigProvider classes to config/config.php
- Updates entries to pipeRoutingMiddleware() to instead pipe the
  zend-expressive-router RouteMiddleware.
- Updates entries to pipeDispatchMiddleware() to instead pipe the
  zend-expressive-router DispatchMiddleware.
- Updates entries to pipe the various Implicit*Middleware to pipe the
  new zend-expressive-router versions.

These changes are made to prepare your application for version 3, and to remove
known deprecation messages.
EOT;

    /**
     * @var null|string Root path of the application being updated; defaults to $PWD
     */
    private $projectDir;

    /**
     * @var null|string Project root in which to make updates.
     */
    public function setProjectDir($path)
    {
        $this->projectDir = $path;
    }

    /**
     * Configure the console command.
     */
    protected function configure()
    {
        $this->setDescription('Migrate an Expressive application to version 2.2.');
        $this->setHelp(self::HELP);
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
        $projectDir = $this->getProjectDir();

        $output->writeln('<info>Migrating application to Expressive 2.2...</info>');

        $output->writeln('<info>- Updating config/config.php</info>');
        $updateConfig = new UpdateConfig();
        $updateConfig($output, $projectDir);

        $output->writeln('<info>- Updating config/pipeline.php</info>');
        $updatePipeline = new UpdatePipeline();
        $updatePipeline($output, $projectDir);

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * Retrieve the project root directory.
     *
     * Uses result of getcwd() if not previously set.
     *
     * @return string
     */
    private function getProjectDir()
    {
        return $this->projectDir ?: realpath(getcwd());
    }
}
