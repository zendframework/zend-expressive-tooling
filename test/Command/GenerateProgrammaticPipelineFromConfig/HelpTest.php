<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Command\GenerateProgrammaticPipelineFromConfig;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\Expressive\Command\GenerateProgrammaticPipelineFromConfig\Help;
use Zend\Stdlib\ConsoleHelper;

class HelpTest extends TestCase
{
    public function testWritesHelpMessageToConsoleUsingCommandProvidedAtInstantiationAndResourceAtInvocation()
    {
        $resource = fopen('php://temp', 'w+');

        $console = $this->prophesize(ConsoleHelper::class);
        $console
            ->writeLine(
                Argument::that(function ($message) {
                    return false !== strstr($message, 'generate-programmatic-pipeline-from-config');
                }),
                true,
                $resource
            )
            ->shouldBeCalled();

        $command = new Help(
            'generate-programmatic-pipeline-from-config',
            $console->reveal()
        );

        $this->assertNull($command($resource));
    }
}
