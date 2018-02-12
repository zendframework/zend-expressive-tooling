<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateHandlerCommand extends Command
{
    const DEFAULT_SRC = '/src';

    const HELP = <<< 'EOT'
Creates a PSR-15 request handler class file named after the provided
class. For a path, it matches the class namespace against PSR-4 autoloader
namespaces in your composer.json.
EOT;

    const HELP_ARG_HANDLER = <<< 'EOT'
Fully qualified class name of the request handler to create. This value
should be quoted to ensure namespace separators are not interpreted as
escape sequences by your shell.
EOT;

    const HELP_OPT_NO_FACTORY = <<< 'EOT'
By default, this command generates a factory for the request handler it
creates, and registers it with the container. Passing this option disables
that feature.
EOT;

    const HELP_OPT_NO_REGISTER = <<< 'EOT'
By default, when this command generates a factory for the request handler it
creates, it registers it with the container. Passing this option disables
registration of the generated factory with the container.
EOT;

    /**
     * Flag indicating whether or not to require the generated handler before
     * generating its factory. By default, this is true, as it is necessary
     * in order for the handler to be reflected. However, during testing, we do
     * not actually generate a handler, so we need a way to disable it.
     *
     * @var bool
     */
    private $requireHandlerBeforeGeneratingFactory = true;

    /**
     * Configure the console command.
     */
    protected function configure()
    {
        $this->setDescription('Create a PSR-15 request handler class file.');
        $this->setHelp(self::HELP);
        $this->addArgument('handler', InputArgument::REQUIRED, self::HELP_ARG_HANDLER);
        $this->addOption('no-factory', null, InputOption::VALUE_NONE, self::HELP_OPT_NO_FACTORY);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_OPT_NO_REGISTER);
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
        $handler = $input->getArgument('handler');

        $output->writeln(sprintf('<info>Creating request handler %s...</info>', $handler));

        $generator = new CreateHandler();
        $path = $generator->process($handler);

        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf(
            '<info>- Created class %s, in file %s</info>',
            $handler,
            $path
        ));

        if (! $input->getOption('no-factory')) {
            $this->requireHandlerBeforeGeneratingFactory && require $path;
            return $this->generateFactory($handler, $input, $output);
        }

        return 0;
    }

    private function generateFactory(string $handlerClass, InputInterface $input, OutputInterface $output) : int
    {
        $factoryInput = new ArrayInput([
            'command'       => 'factory:create',
            'class'         => $handlerClass,
            '--no-register' => $input->getOption('no-register'),
        ]);
        $command = $this->getApplication()->find('factory:create');
        return $command->run($factoryInput, $output);
    }
}
