<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\MigrateMiddlewareToRequestHandler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateMiddlewareToRequestHandlerCommand extends Command
{
    private const DEFAULT_SRC = '/src';

    private const HELP = <<< 'EOT'
Migrate PSR-15 middleware to request handlers.

Scans all PHP files under the --src directory for PSR-15 middleware. When it
encounters middleware class files where the "middleware" does not call on the
second argument (the handler or "delegate"), it converts them to request
handlers.
EOT;

    private const HELP_OPT_SRC = <<< 'EOT'
Specify a path to PHP files under which to migrate PSR-15 middleware to request
handlers. If not specified, assumes src/ under the current working path.
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
        $this->setDescription('Migrate PSR-15 middleware to request handlers');
        $this->setHelp(self::HELP);
        $this->addOption('src', 's', InputOption::VALUE_REQUIRED, self::HELP_OPT_SRC);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $src = $this->getSrcDir($input);

        $output->writeln(sprintf(
            '<info>Scanning "%s" for PSR-15 middleware to convert...</info>',
            $src
        ));

        $converter = new ConvertMiddleware($output);
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
