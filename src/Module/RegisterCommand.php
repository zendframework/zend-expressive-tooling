<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module;

use Symfony\Component\Console\Command\Command;
use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Zend\ComponentInstaller\Injector\InjectorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZF\ComposerAutoloading\Command\Enable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class RegisterCommand extends Command
{
    use CommandCommonTrait;

    const HELP = <<< 'EOT'
Register an existing middleware module with the application, by:

- Ensuring a PSR-4 autoloader entry is present in composer.json, and the
  autoloading rules have been generated.
- Ensuring the ConfigProvider class for the module is registered with the
  application configuration.
EOT;

    const HELP_ARG_MODULE = 'The module to register with the application';

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setDescription('Register a middleware module with the application');
        $this->setHelp(self::HELP);
        $this->addDefaultOptionsAndArguments();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        $composer = $input->getOption('composer') ?: 'composer';
        $modulesPath = $this->getModulesPath($input);

        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = sprintf('%s\ConfigProvider', $module);
        if (! $injector->isRegistered($configProvider)) {
            $injector->inject(
                $configProvider,
                InjectorInterface::TYPE_CONFIG_PROVIDER
            );
        }

        try {
            $enable = new Enable($this->projectDir, $modulesPath, $composer);
            $enable->setMoveModuleClass(false);
            $enable->process($module);
        } catch (RuntimeException $ex) {
            $console = $this->getErrorConsole($output);
            $console->writeln('<error>Error during execution:</error>');
            $console->writeln(sprintf('  <error>%s</error>', $ex->getMessage()));
            return 1;
        }

        $output->writeln(sprintf('Registered autoloading rules and added configuration entry for module %s', $module));
        return 0;
    }
}