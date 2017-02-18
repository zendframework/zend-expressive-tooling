<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module\Command;

use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use ZF\ComposerAutoloading\Command;

class Register extends AbstractCommand
{
    /**
     * Registers the expressive module in configuration and composer autoloading.
     *
     * @return void
     */
    public function process()
    {
        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = $this->moduleName . '\ConfigProvider';
        if (! $injector->isRegistered($configProvider)) {
            $injector->inject(
                $configProvider,
                ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER
            );
        }

        $composerAutoloading = new Command($this->projectDir);
        $composerAutoloading([
            'autoload-module-via-composer',
            '--composer',
            $this->composer,
            '--modules-path',
            $this->modulesPath,
            $this->moduleName,
        ]);
    }
}
