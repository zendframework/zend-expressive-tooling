<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    const HELP = <<< 'EOT'
Create a new middleware module for the application.

- Creates an appropriate module structure containing a source code tree,
  templates tree, and ConfigProvider class.
- Adds a PSR-4 autoloader to composer.json, and regenerates the
  autoloading rules.
- Registers the ConfigProvider class for the module with the application
  configuration.
EOT;

    const HELP_ARG_MODULE = 'The module to create and register with the application.';

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setDescription('Create and register a middleware module with the application');
        $this->setHelp(self::HELP);
        CommandCommonOptions::addDefaultOptionsAndArguments($this);
    }

    /**
     * Execute command
     *
     * Executes command by creating new module tree, and then executing
     * the "register" command with the same module name.
     *
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        $composer = $input->getOption('composer') ?: 'composer';
        $modulesPath = CommandCommonOptions::getModulesPath($input);

        $creation = new Create();
        $message = $creation->process($module, $modulesPath, getcwd());
        $output->writeln(sprintf('<info>%s</info>', $message));

        $registerCommand = $this->getRegisterCommandName();
        $register = $this->getApplication()->find($registerCommand);
        return $register->run(new ArrayInput([
            'command'        => $registerCommand,
            'module'         => $module,
            '--composer'     => $composer,
            '--modules-path' => $modulesPath,
        ]), $output);
    }

    /**
     * Retrieve the name of the "register" command.
     *
     * Varies with usage of the "expressive" vs "expressive-module" command.
     *
     * @return string
     */
    private function getRegisterCommandName()
    {
        return 0 === strpos($this->getName(), 'module:')
            ? 'module:register'
            : 'register';
    }
}
