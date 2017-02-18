<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module\Command;

use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;

class Deregister extends AbstractCommand
{
    /**
     * Deregisters the expressive module from configuration and composer autoloading.
     *
     * @return void
     */
    public function process()
    {
        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = $this->moduleName . '\ConfigProvider';
        if ($injector->isRegistered($configProvider)) {
            $injector->remove($configProvider);
        }

        // TODO: remove from composer autoloading
        // will be nice to have it in zfcampus/zf-composer-autoloading
    }
}
