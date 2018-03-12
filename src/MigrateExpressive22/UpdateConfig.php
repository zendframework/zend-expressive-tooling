<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\MigrateExpressive22;

use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfig
{
    /**
     * @param string $projectPath
     * @return void
     */
    public function __invoke(OutputInterface $output, $projectPath)
    {
        $filename = sprintf('%s/config/config.php', $projectPath);
        $contents = file_get_contents($filename);

        $components = [
            \Zend\Expressive\Router\ConfigProvider::class,
            \Zend\Expressive\ConfigProvider::class,
        ];

        $pattern = sprintf(
            "/(new (?:%s?%s)?ConfigAggregator\(\s*(?:array\(|\[)\s*)(?:\r|\n|\r\n)(\s*)/",
            preg_quote('\\'),
            preg_quote('Zend\ConfigAggregator\\')
        );

        $replacementTemplate = "\$1\n\$2\\%s::class,\n\$2";

        foreach ($components as $component) {
            $output->writeln(sprintf(
                '<info>Adding %s to config/config.php</info>',
                $component
            ));
            $replacement = sprintf($replacementTemplate, $component);
            $contents = preg_replace($pattern, $replacement, $contents);
        }

        file_put_contents($filename, $contents);
    }
}
