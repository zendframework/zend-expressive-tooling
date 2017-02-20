<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module\Command;

use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Zend\Expressive\Tooling\Module\Exception;
use ZF\ComposerAutoloading\Command\Disable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class Deregister extends AbstractCommand
{
    /**
     * Deregisters the expressive module from configuration and composer autoloading.
     *
     * {@inheritdoc}
     */
    public function process()
    {
        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = $this->moduleName . '\ConfigProvider';
        if ($injector->isRegistered($configProvider)) {
            $injector->remove($configProvider);
        }

        try {
            $disable = new Disable($this->projectDir, $this->modulesPath, $this->composer);
            return $disable($this->moduleName);
        } catch (RuntimeException $ex) {
            throw new Exception\RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }
}
