<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Command\MigrateOriginalMessageCalls;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Expressive\Command\MigrateOriginalMessageCalls\ConvertOriginalMessageCalls;

class ConvertOriginalMessageCallsTest extends TestCase
{
    use ProjectSetupTrait;

    public function testConvertsFilesAndEmitsInfoMessagesAsExpected()
    {
        $dir = vfsStream::setup('migrate');
        $this->setupSrcDir($dir);
        $path = vfsStream::url('migrate');

        $console = $this->setupConsoleHelper();

        $converter = new ConvertOriginalMessageCalls($console->reveal());
        $converter->process($path);
    }
}
