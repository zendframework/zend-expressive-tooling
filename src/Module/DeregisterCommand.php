<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use ZF\ComposerAutoloading\Command\Disable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class DeregisterCommand extends Command
{
    use CommandCommonTrait;

    const HELP = <<< 'EOT'
Deregister an existing middleware module from the application, by:

- Removing the associated PSR-4 autoloader entry from composer.json, and
  regenerating autoloading rules.
- Removing the associated ConfigProvider class for the module from the
  application configuration.
EOT;

    const HELP_ARG_MODULE = 'The module to register with the application';

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setDescription('Deregister a middleware module from the application');
        $this->setHelp(self::HELP);
        $this->addDefaultOptionsAndArguments();
    }

    /**
     * Deregister module.
     *
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        $composer = $input->getOption('composer') ?: 'composer';
        $modulesPath = $this->getModulesPath($input);

        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = sprintf('%s\ConfigProvider', $module);
        if ($injector->isRegistered($configProvider)) {
            $injector->remove($configProvider);
        }

        try {
            $disable = new Disable($this->projectDir, $modulesPath, $composer);
            $disable->process($module);
        } catch (RuntimeException $ex) {
            $console = $this->getErrorConsole($output);
            $console->writeln('<error>Error during execution:</error>');
            $console->writeln(sprintf('  <error>%s</error>', $ex->getMessage()));
            return 1;
        }

        $output->writeln(sprintf('Removed autoloading rules and configuration entries for module %s', $module));
        return 0;
    }
}