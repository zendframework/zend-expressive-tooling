<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module\Command;

use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Zend\ComponentInstaller\Injector\InjectorInterface;
use Zend\Expressive\Tooling\Module\Exception;
use ZF\ComposerAutoloading\Command\Enable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class Register extends AbstractCommand
{
    /**
     * Registers the expressive module in configuration and composer autoloading.
     *
     * {@inheritdoc}
     */
    public function process($moduleName)
    {
        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = sprintf('%s\ConfigProvider', $moduleName);
        if (! $injector->isRegistered($configProvider)) {
            $injector->inject(
                $configProvider,
                InjectorInterface::TYPE_CONFIG_PROVIDER
            );
        }

        try {
            $enable = new Enable($this->projectDir, $this->modulesPath, $this->composer);
            $enable->setMoveModuleClass(false);
            return $enable->process($moduleName);
        } catch (RuntimeException $ex) {
            throw new Exception\RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }
}
