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
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\MigrateExpressive22\MigrateExpressive22Command;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MigrateOriginalMessageCallsCommandTest extends TestCase
{
    protected function setUp()
    {
        $this->root = vfsStream::setup('expressive22');
        $this->url = vfsStream::url('expressive22');
        mkdir($this->url . '/config');
        touch($this->url . '/config/config.php');
        touch($this->url . '/config/pipeline.php');

        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new MigrateExpressive22Command('migrate:expressive-v2.2');
        $this->command->setProjectDir($this->url);
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('Migrate an Expressive application to version 2.2', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(MigrateExpressive22Command::HELP, $this->command->getHelp());
    }

    public function testCommandUpdatesConfigAndPipeline()
    {
        $config = sprintf('%s/config/config.php', $this->url);
        $pipeline = sprintf('%s/config/pipeline.php', $this->url);

        file_put_contents($config, $this->getOriginalConfig());
        file_put_contents($pipeline, $this->getOriginalPipeline());

        $this->output
            ->writeln(Argument::containingString('Migrating application to Expressive 2.2'))
            ->shouldBeCalled();

        $this->output->writeln(Argument::containingString('- Updating config/config.php'))->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Adding Zend\Expressive\Router\ConfigProvider to config'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Adding Zend\Expressive\ConfigProvider to config'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('- Updating config/pipeline.php'))
            ->shouldBeCalled();
        $this->output->writeln(Argument::containingString('pipeRoutingMiddleware()'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('pipeDispatchMiddleware()'))->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();
        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));

        $configAfterUpdate = file_get_contents($config);
        $pipelineAfterUpdate = file_get_contents($pipeline);

        $this->assertEquals(
            $this->getExpectedConfig(),
            $configAfterUpdate,
            'Configuration was not updated as expected'
        );
        $this->assertEquals(
            $this->getExpectedPipeline(),
            $pipelineAfterUpdate,
            'Pipeline was not updated as expected'
        );
    }

    private function getOriginalConfig()
    {
        return <<< 'EOT'
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
    }

    private function getOriginalPipeline()
    {
        return <<< 'EOT'
$app->pipeRoutingMiddleware();
$app->pipe(\Zend\Expressive\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Zend\Expressive\Middleware\ImplicitOptionsMiddleware::class);
$app->pipeDispatchMiddleware();
EOT;
    }

    public function getExpectedConfig()
    {
        return <<< 'EOT'
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
    }

    public function getExpectedPipeline()
    {
        return <<< 'EOT'
$app->pipe(\Zend\Expressive\Router\Middleware\RouteMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\DispatchMiddleware::class);
EOT;
    }
}
