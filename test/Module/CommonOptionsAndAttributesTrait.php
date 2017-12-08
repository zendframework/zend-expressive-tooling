<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\Module;

trait CommonOptionsAndAttributesTrait
{
    public function testConfigureSetsExpectedArgument()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('module'));
        $argument = $definition->getArgument('module');
        $this->assertTrue($argument->isRequired());
        $this->assertEquals($this->expectedModuleArgumentDescription, $argument->getDescription());
    }

    public function testConfigureSetsExpectedComposerOption()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('composer'));
        $option = $definition->getOption('composer');
        $this->assertTrue($option->isValueRequired());
        $this->assertContains('path to the composer binary', $option->getDescription());
    }

    public function testConfigureSetsExpectedPathOption()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('modules-path'));
        $option = $definition->getOption('modules-path');
        $this->assertTrue($option->isValueRequired());
        $this->assertContains('path to the modules directory', $option->getDescription());
    }
}
