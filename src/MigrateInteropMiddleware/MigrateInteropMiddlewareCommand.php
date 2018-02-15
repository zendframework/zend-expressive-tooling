<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\MigrateInteropMiddleware;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated since 1.0.0, and scheduled for removal with 2.0.0
 */
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
    public function setProjectDir(?string $path) : void
    {
        $this->projectDir = $path;
    }

    /**
     * Retrieve the project root directory.
     *
     * Uses result of getcwd() if not previously set.
     */
    private function getProjectDir() : string
    {
        return $this->projectDir ?: getcwd();
    }

    protected function configure() : void
    {
        $this->setDescription('Migrate interop middlewares and delegators');
        $this->setHelp(self::HELP);
        $this->addOption('src', 's', InputOption::VALUE_REQUIRED, self::HELP_OPT_SRC);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $src = $this->getSrcDir($input);

        $output->writeln('<info>Scanning for usage of Interop Middlewares...</info>');

        $converter = new ConvertInteropMiddleware($output);
        $converter->process($src);

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * @throws ArgvException
     */
    private function getSrcDir(InputInterface $input) : string
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
