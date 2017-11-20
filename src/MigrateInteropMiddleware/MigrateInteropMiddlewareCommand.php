<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\MigrateInteropMiddleware;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateInteropMiddlewareCommand extends Command
{
    private const DEFAULT_SRC = '/src';

    private const HELP = <<< 'EOT'
Migrate an Expressive application to PSR-15 middlewares.

Scans all PHP files under the --src directory for interop middlewares
and delegators. Changes imported interop classes to PSR-15 interfaces,
keeps aliases and adds return type if it is not present.
EOT;

    private const HELP_OPT_SRC = <<< 'EOT'
Specify a path to PHP files to migrate interop middlewares.
If not specified, assumes src/ under the current working path.
EOT;

    /**
     * @var null|string Path from which to resolve default src directory
     */
    private $projectDir;

    /**
     * @var null|string Project root against which to scan.
     */
    public function setProjectDir($path)
    {
        $this->projectDir = $path;
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
        return $this->projectDir ?: getcwd();
    }

    protected function configure()
    {
        $this->setDescription('Migrate interop middlewares and delegators');
        $this->setHelp(self::HELP);
        $this->addOption('src', 's', InputOption::VALUE_REQUIRED, self::HELP_OPT_SRC);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $this->getSrcDir($input);

        $output->writeln('<info>Scanning for usage of Interop Middlewares...</info>');

        $converter = new ConvertInteropMiddleware($output);
        $converter->process($src);

//        if ($converter->originalResponseFound()) {
//            $output->writeln('<error>One or more files contained calls to getOriginalResponse().</error>');
//            $output->writeln('<info>Check the above logs to determine which files need attention.</info>');
//            $output->writeln(self::TEMPLATE_RESPONSE_DETAILS);
//        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws ArgvException
     */
    private function getSrcDir(InputInterface $input)
    {
        $path = $input->getOption('src') ?: self::DEFAULT_SRC;
        $path = $this->getProjectDir() . '/' . $path;

        if (! is_dir($path)) {
            throw new ArgvException(sprintf(
                'Invalid --src argument "%s"; directory does not exist',
                $path
            ));
        }

        return $path;
    }
}
