<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\MigrateOriginalMessageCalls;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\MigrateOriginalMessageCalls\ConvertOriginalMessageCalls;

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

        $this->assertExpected($path);
    }
}
