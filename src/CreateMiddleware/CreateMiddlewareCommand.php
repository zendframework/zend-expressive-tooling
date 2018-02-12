<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateMiddleware;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMiddlewareCommand extends Command
{
    public const DEFAULT_SRC = '/src';

    public const HELP = <<< 'EOT'
Creates a PSR-15 middleware class file named after the provided class. For a
path, it matches the class namespace against PSR-4 autoloader namespaces in
your composer.json.
EOT;

    public const HELP_ARG_MIDDLEWARE = <<< 'EOT'
Fully qualified class name of the middleware to create. This value
should be quoted to ensure namespace separators are not interpreted as
escape sequences by your shell.
EOT;

    /**
     * Configure the console command.
     */
    protected function configure() : void
    {
        $this->setDescription('Create a PSR-15 middleware class file.');
        $this->setHelp(self::HELP);
        $this->addArgument('middleware', InputArgument::REQUIRED, self::HELP_ARG_MIDDLEWARE);
    }

    /**
     * Execute console command.
     *
     * @return int Exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $middleware = $input->getArgument('middleware');

        $output->writeln(sprintf('<info>Creating middleware %s...</info>', $middleware));

        $generator = new CreateMiddleware();
        $path = $generator->process($middleware);

        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf(
            '<info>- Created class %s, in file %s</info>',
            $middleware,
            $path
        ));

        return 0;
    }
}
