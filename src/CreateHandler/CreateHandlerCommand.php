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
    public const DEFAULT_SRC = '/src';

    public const HELP_HANDLER_DESCRIPTION = 'Create a PSR-15 request handler class file.';

    public const HELP_HANDLER = <<< 'EOT'
Creates a PSR-15 request handler class file named after the provided
class. For a path, it matches the class namespace against PSR-4 autoloader
namespaces in your composer.json.
EOT;

    public const HELP_HANDLER_ARG_HANDLER = <<< 'EOT'
Fully qualified class name of the request handler to create. This value
should be quoted to ensure namespace separators are not interpreted as
escape sequences by your shell.
EOT;

    const HELP_HANDLER_OPT_NO_FACTORY = <<< 'EOT'
By default, this command generates a factory for the request handler it
creates, and registers it with the container. Passing this option disables
that feature.
EOT;

    const HELP_HANDLER_OPT_NO_REGISTER = <<< 'EOT'
By default, when this command generates a factory for the request handler it
creates, it registers it with the container. Passing this option disables
registration of the generated factory with the container.
EOT;

    public const HELP_ACTION_DESCRIPTION = 'Create an action class file.';

    public const HELP_ACTION = <<< 'EOT'
Creates an action class file named after the provided class. For a path, it
matches the class namespace against PSR-4 autoloader namespaces in your
composer.json.
EOT;

    public const HELP_ACTION_ARG_ACTION = <<< 'EOT'
Fully qualified class name of the action class to create. This value
should be quoted to ensure namespace separators are not interpreted as
escape sequences by your shell.
EOT;

    const HELP_ACTION_OPT_NO_FACTORY = <<< 'EOT'
By default, this command generates a factory for the action class it creates,
and registers it with the container. Passing this option disables that
feature.
EOT;

    const HELP_ACTION_OPT_NO_REGISTER = <<< 'EOT'
By default, when this command generates a factory for the action class it
creates, it registers it with the container. Passing this option disables
registration of the generated factory with the container.
EOT;

    const STATUS_HANDLER_TEMPLATE = '<info>Creating request handler %s...</info>';

    const STATUS_ACTION_TEMPLATE = '<info>Creating action %s...</info>';

    /**
     * Name of the argument that resolves to the new handler's name.
     *
     * @var string
     */
    private $handlerArgument = 'handler';

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
     *
     * If the command is named `action:create`, this method sets the
     * $handlerArgument to "action", and then invokes the configureAction()
     * method before returning. Otherwise, it configures the command for
     * producing a handler.
     */
    protected function configure() : void
    {
        if (0 === strpos($this->getName(), 'action:')) {
            $this->handlerArgument = 'action';
            $this->configureAction();
            return;
        }

        $this->setDescription(self::HELP_HANDLER_DESCRIPTION);
        $this->setHelp(self::HELP_HANDLER);
        $this->addArgument('handler', InputArgument::REQUIRED, self::HELP_HANDLER_ARG_HANDLER);
        $this->addOption('no-factory', null, InputOption::VALUE_NONE, self::HELP_HANDLER_OPT_NO_FACTORY);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_HANDLER_OPT_NO_REGISTER);
    }

    protected function configureAction() : void
    {
        $this->setDescription(self::HELP_ACTION_DESCRIPTION);
        $this->setHelp(self::HELP_ACTION);
        $this->addArgument('action', InputArgument::REQUIRED, self::HELP_ACTION_ARG_ACTION);
        $this->addOption('no-factory', null, InputOption::VALUE_NONE, self::HELP_ACTION_OPT_NO_FACTORY);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_ACTION_OPT_NO_REGISTER);
    }

    /**
     * Execute console command.
     *
     * @return int Exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $handler = $input->getArgument($this->handlerArgument);

        $template = $this->handlerArgument === 'action'
            ? self::STATUS_ACTION_TEMPLATE
            : self::STATUS_HANDLER_TEMPLATE;
        $output->writeln(sprintf($template, $handler));

        $generator = new CreateHandler();
        $path = $generator->process($handler);

        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf(
            '<info>- Created class %s, in file %s</info>',
            $handler,
            $path
        ));

        if (! $input->getOption('no-factory')) {
            if ($this->requireHandlerBeforeGeneratingFactory) {
                require $path;
            }
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
