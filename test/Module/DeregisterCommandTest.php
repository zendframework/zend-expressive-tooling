<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\Module;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Zend\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Zend\Expressive\Tooling\Module\DeregisterCommand;
use Zend\Expressive\Tooling\Module\Exception;
use ZF\ComposerAutoloading\Command\Disable;
use ZF\ComposerAutoloading\Exception\RuntimeException;

class DeregisterCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $composer = 'my-composer';

    /** @var Deregister */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->dir = vfsStream::setup('project');
        $this->command = new DeregisterCommand($this->dir->url(), 'my-modules', $this->composer);
    }

    public function removedDisabled()
    {
        return [
            // $removed, $disabled
            [true,       true],
            [true,       false],
            [false,      true],
            [false,      false],
        ];
    }

    /**
     * @dataProvider removedDisabled
     *
     * @param bool $removed
     * @param bool $disabled
     */
    public function testRemoveFromConfigurationAndDisableModule($removed, $disabled)
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn($removed)
            ->once();
        if ($removed) {
            $injectorMock
                ->shouldReceive('remove')
                ->with('MyApp\ConfigProvider')
                ->once();
        } else {
            $injectorMock
                ->shouldNotReceive('remove');
        }

        $disableMock = Mockery::mock('overload:' . Disable::class);
        $disableMock
            ->shouldReceive('process')
            ->with('MyApp')
            ->andReturn($disabled)
            ->once();

        $this->assertEquals(
            'Removed autoloading rules and configuration entries for module MyApp',
            $this->command->process('MyApp')
        );
    }

    public function testThrowsRuntimeExceptionFromModuleWhenDisableThrowsException()
    {
        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(false)
            ->once();

        $disableMock = Mockery::mock('overload:' . Disable::class);
        $disableMock
            ->shouldReceive('process')
            ->with('MyApp')
            ->andThrow(RuntimeException::class, 'Testing Exception Message')
            ->once();

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Testing Exception Message');
        $this->command->process('MyApp');
    }
}
