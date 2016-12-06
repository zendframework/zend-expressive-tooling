<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Command\MigrateOriginalMessageCalls;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\Expressive\Command\MigrateOriginalMessageCalls\Help;
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
                    return false !== strstr($message, 'migrate-original-message-calls');
                }),
                true,
                $resource
            )
            ->shouldBeCalled();

        $command = new Help(
            'migrate-original-message-calls',
            $console->reveal()
        );

        $this->assertNull($command($resource));
    }

    public function testTruncatesCommandToBasenameIfItIsARealpath()
    {
        $resource = fopen('php://temp', 'w+');

        $console = $this->prophesize(ConsoleHelper::class);
        $console
            ->writeLine(
                Argument::that(function ($message) {
                    return false !== strstr($message, basename(__FILE__));
                }),
                true,
                $resource
            )
            ->shouldBeCalled();

        $command = new Help(
            realpath(__FILE__),
            $console->reveal()
        );

        $this->assertNull($command($resource));
    }
}
