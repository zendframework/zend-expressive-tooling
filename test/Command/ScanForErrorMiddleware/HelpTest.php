<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Command\ScanForErrorMiddleware;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\Expressive\Command\ScanForErrorMiddleware\Help;
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
                    return false !== strstr($message, 'scan-for-error-middleware');
                }),
                true,
                $resource
            )
            ->shouldBeCalled();

        $command = new Help(
            'scan-for-error-middleware',
            $console->reveal()
        );

        $this->assertNull($command($resource));
    }
}
