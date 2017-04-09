<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module;

use Zend\Expressive\Tooling\Module\Exception;

class Create
{
    const TEMPLATE_CONFIG_PROVIDER = <<< 'EOT'
<?php

namespace %1$s;

/**
 * The configuration provider for the %1$s module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'invokables' => [
            ],
            'factories'  => [
            ],
        ];
    }

    /**
     * Returns the templates configuration
     *
     * @return array
     */
    public function getTemplates()
    {
        return [
            'paths' => [
                'app'    => [__DIR__ . '/../templates/app'],
                'error'  => [__DIR__ . '/../templates/error'],
                'layout' => [__DIR__ . '/../templates/layout'],
            ],
        ];
    }
}

EOT;

    /**
     * Create source tree for the expressive module.
     *
     * @param string $moduleName
     * @param string $modulesPath
     * @param string $projectDir
     * @return string
     */
    public function process($moduleName, $modulesPath, $projectDir)
    {
        $modulePath = sprintf('%s/%s/%s', $projectDir, $modulesPath, $moduleName);

        $this->createDirectoryStructure($modulePath, $moduleName);
        $this->createConfigProvider($modulePath, $moduleName);

        return sprintf('Created module %s in %s', $moduleName, $modulePath);
    }

    /**
     * Creates directory structure for new expressive module.
     *
     * @param string $modulePath
     * @param string $moduleName
     * @return void
     * @throws Exception\RuntimeException
     */
    private function createDirectoryStructure($modulePath, $moduleName)
    {
        if (file_exists($modulePath)) {
            throw new Exception\RuntimeException(sprintf(
                'Module "%s" already exists',
                $moduleName
            ));
        }

        if (! mkdir($modulePath)) {
            throw new Exception\RuntimeException(sprintf(
                'Module directory "%s" cannot be created',
                $modulePath
            ));
        }

        if (! mkdir($modulePath . '/src')) {
            throw new Exception\RuntimeException(sprintf(
                'Module source directory "%s/src" cannot be created',
                $modulePath
            ));
        }

        if (! mkdir($modulePath . '/templates')) {
            throw new Exception\RuntimeException(sprintf(
                'Module templates directory "%s/templates" cannot be created',
                $modulePath
            ));
        }
    }

    /**
     * Creates ConfigProvider for new expressive module.
     *
     * @param string $modulePath
     * @param string $moduleName
     * @return void
     */
    private function createConfigProvider($modulePath, $moduleName)
    {
        file_put_contents(
            sprintf('%s/src/ConfigProvider.php', $modulePath),
            sprintf(
                self::TEMPLATE_CONFIG_PROVIDER,
                $moduleName
            )
        );
    }
}
