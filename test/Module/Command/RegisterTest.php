<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\Module\Command;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Zend\ComponentInstaller\Injector\InjectorInterface;
use Zend\Expressive\Tooling\Module\Command\Register;
use Zend\Expressive\Tooling\Module\Exception;
use ZF\ComposerAutoloading\Command\Enable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class RegisterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $composer = 'my-composer';

    /** @var Register */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->dir = vfsStream::setup('project');
        $this->command = new Register($this->dir->url(), 'my-modules', $this->composer);
    }

    public function injectedEnabled()
    {
        return [
            // $injected, $enabled
            [true,        true],
            [true,        false],
            [false,       true],
            [false,       false],
        ];
    }

    /**
     * @dataProvider injectedEnabled
     *
     * @param bool $injected
     * @param bool $enabled
     */
    public function testInjectConfigurationAndEnableModule($injected, $enabled)
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(! $injected)
            ->once();
        if ($injected) {
            $injectorMock
                ->shouldReceive('inject')
                ->with('MyApp\ConfigProvider', InjectorInterface::TYPE_CONFIG_PROVIDER)
                ->once();
        } else {
            $injectorMock
                ->shouldNotReceive('inject');
        }

        $enableMock = Mockery::mock('overload:' . Enable::class);
        $enableMock
            ->shouldReceive('setMoveModuleClass')
            ->with(false)
            ->once();
        $enableMock
            ->shouldReceive('process')
            ->with('MyApp')
            ->andReturn($enabled)
            ->once();

        $this->assertSame($enabled, $this->command->process('MyApp'));
    }

    public function testThrowsRuntimeExceptionFromModuleWhenEnableThrowsException()
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(true)
            ->once();

        $enableMock = Mockery::mock('overload:' . Enable::class);
        $enableMock
            ->shouldReceive('setMoveModuleClass')
            ->with(false)
            ->once();
        $enableMock
            ->shouldReceive('process')
            ->with('MyApp')
            ->andThrow(RuntimeException::class, 'Testing Exception Message')
            ->once();

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Testing Exception Message');
        $this->command->process('MyApp');
    }
}
