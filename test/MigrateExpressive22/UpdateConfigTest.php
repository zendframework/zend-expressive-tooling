<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\MigrateExpressive22;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\MigrateExpressive22\UpdateConfig;

class UpdateConfigTest extends TestCase
{
    public function setUp()
    {
        $this->root = vfsStream::setup('expressive22');
        $this->url = vfsStream::url('expressive22');
        mkdir($this->url . '/config');
        touch($this->url . '/config/config.php');
    }

    public function testInjectsProvidersInStandardSkeletonSetup()
    {
        $originalConfig = <<< 'EOT'
<?php

use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'data/config-cache.php',
];

$aggregator = new ConfigAggregator([
    // Include cache configuration
    new ArrayProvider($cacheConfig),

    // Default App module config
    App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);

return $aggregator->getMergedConfig();
EOT;

        $expectedConfig = <<< 'EOT'
<?php

use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'data/config-cache.php',
];

$aggregator = new ConfigAggregator([
    \Zend\Expressive\ConfigProvider::class,
    \Zend\Expressive\Router\ConfigProvider::class,
    // Include cache configuration
    new ArrayProvider($cacheConfig),

    // Default App module config
    App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);

return $aggregator->getMergedConfig();
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output
            ->writeln(Argument::containingString('Adding Zend\Expressive\Router\ConfigProvider to config'))
            ->shouldBeCalled();
        $output
            ->writeln(Argument::containingString('Adding Zend\Expressive\ConfigProvider to config'))
            ->shouldBeCalled();

        $updateConfig = new UpdateConfig();

        $this->assertNull($updateConfig($output->reveal(), $this->url));
    }

    public function testInjectsProvidersWhenConfigReferencesFullyQualifiedAggregatorClassName()
    {
        $originalConfig = <<< 'EOT'
$aggregator = new \Zend\ConfigAggregator\ConfigAggregator([
    // Include cache configuration
    new \Zend\ConfigAggregator\ArrayProvider($cacheConfig),

    // Default App module config
    \App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new \Zend\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new \Zend\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);
EOT;

        $expectedConfig = <<< 'EOT'
$aggregator = new \Zend\ConfigAggregator\ConfigAggregator([
    \Zend\Expressive\ConfigProvider::class,
    \Zend\Expressive\Router\ConfigProvider::class,
    // Include cache configuration
    new \Zend\ConfigAggregator\ArrayProvider($cacheConfig),

    // Default App module config
    \App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new \Zend\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new \Zend\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output
            ->writeln(Argument::containingString('Adding Zend\Expressive\Router\ConfigProvider to config'))
            ->shouldBeCalled();
        $output
            ->writeln(Argument::containingString('Adding Zend\Expressive\ConfigProvider to config'))
            ->shouldBeCalled();

        $updateConfig = new UpdateConfig();

        $this->assertNull($updateConfig($output->reveal(), $this->url));
    }
}
