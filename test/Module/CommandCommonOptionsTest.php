<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Module;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\InputInterface;
use Zend\Expressive\Tooling\Module\CommandCommonOptions;

class CommandCommonOptionsTest extends TestCase
{
    /** @var InputInterface|ObjectProphecy */
    private $input;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
    }

    public function testGetModulesPathGetsOptionsFromInput() : void
    {
        $this->input->getOption('modules-path')->willReturn('path-from-input');
        $config[CommandCommonOptions::class]['--modules-path'] = 'path-from-config';

        $this->assertEquals(
            'path-from-input',
            CommandCommonOptions::getModulesPath($this->input->reveal(), $config)
        );
    }

    public function testGetModulesPathGetsOptionsFromConfig() : void
    {
        $this->input->getOption('modules-path')->willReturn(null);
        $config[CommandCommonOptions::class]['--modules-path'] = 'path-from-config';

        $this->assertEquals(
            'path-from-config',
            CommandCommonOptions::getModulesPath($this->input->reveal(), $config)
        );
    }

    public function testGetModulesPathGetsDefaultOption() : void
    {
        $this->input->getOption('modules-path')->willReturn(null);

        $this->assertEquals(
            'src',
            CommandCommonOptions::getModulesPath($this->input->reveal())
        );
    }
}
