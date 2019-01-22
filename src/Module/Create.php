<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Module;

use Zend\Expressive\Tooling\TemplateResolutionTrait;

class Create
{
    use TemplateResolutionTrait;

    public const TEMPLATE_CONFIG_PROVIDER = <<< 'EOT'
<?php

declare(strict_types=1);

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
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
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
     */
    public function getTemplates() : array
    {
        return [
            'paths' => [
                '%2$s'    => [__DIR__ . '/../templates/'],
            ],
        ];
    }
}

EOT;

    /**
     * Create source tree for the expressive module.
     */
    public function process(string $moduleName, string $modulesPath, string $projectDir) : string
    {
        $modulePath = sprintf('%s/%s/%s', $projectDir, $modulesPath, $moduleName);

        $this->createDirectoryStructure($modulePath, $moduleName);
        $this->createConfigProvider($modulePath, $moduleName);

        return sprintf('Created module %s in %s', $moduleName, $modulePath);
    }

    /**
     * Creates directory structure for new expressive module.
     *
     * @throws RuntimeException
     */
    private function createDirectoryStructure(string $modulePath, string $moduleName) : void
    {
        if (file_exists($modulePath)) {
            throw new RuntimeException(sprintf(
                'Module "%s" already exists',
                $moduleName
            ));
        }

        if (! mkdir($modulePath)) {
            throw new RuntimeException(sprintf(
                'Module directory "%s" cannot be created',
                $modulePath
            ));
        }

        if (! mkdir($modulePath . '/src')) {
            throw new RuntimeException(sprintf(
                'Module source directory "%s/src" cannot be created',
                $modulePath
            ));
        }

        $templatePath = sprintf('%s/templates', $modulePath);
        if (! mkdir($templatePath)) {
            throw new RuntimeException(sprintf(
                'Module templates directory "%s" cannot be created',
                $templatePath
            ));
        }
    }

    /**
     * Creates ConfigProvider for new expressive module.
     */
    private function createConfigProvider(string $modulePath, string $moduleName) : void
    {
        file_put_contents(
            sprintf('%s/src/ConfigProvider.php', $modulePath),
            sprintf(
                self::TEMPLATE_CONFIG_PROVIDER,
                $moduleName,
                $this->normalizeTemplateIdentifier($moduleName)
            )
        );
    }
}
