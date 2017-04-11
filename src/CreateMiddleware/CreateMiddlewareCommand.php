<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\CreateMiddleware;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMiddlewareCommand extends Command
{
    const DEFAULT_SRC = '/src';

    const TEMPLATE_RESPONSE_DETAILS = <<< 'EOT'
To correct these files, look for a request argument, and update the
following calls ($response may be a different variable):

    $response->getOriginalResponse()

to:

    $request->getAttribute('originalResponse', $response)

($request may be a different variable)
EOT;

    const HELP = <<< 'EOT'
Creates an http-interop middleware class file named after the provided
class. For a path, it matches the class namespace against PSR-4 autoloader
namespaces in your composer.json.
EOT;

    const HELP_ARG_MIDDLEWARE = <<< 'EOT'
Fully qualified class name of the middleware to create. This value
should be quoted to ensure namespace separators are not interpreted as
escape sequences by your shell.
EOT;

    /**
     * Configure the console command.
     */
    protected function configure()
    {
        $this->setDescription('Create an http-interop middleware class file.');
        $this->setHelp(self::HELP);
        $this->addArgument('middleware', InputArgument::REQUIRED, self::HELP_ARG_MIDDLEWARE);
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
