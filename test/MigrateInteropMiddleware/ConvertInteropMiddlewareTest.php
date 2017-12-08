<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\MigrateInteropMiddleware\ConvertInteropMiddleware;

class ConvertInteropMiddlewareTest extends TestCase
{
    use ProjectSetupTrait;

    public function testConvertsFilesAndEmitsInfoMessagesAsExpected()
    {
        $dir = vfsStream::setup('migrate');
        $this->setupSrcDir($dir);
        $path = vfsStream::url('migrate');

        $console = $this->setupConsoleHelper();

        $converter = new ConvertInteropMiddleware($console->reveal());
        $converter->process($path);

        $this->assertExpected($path);
    }
}
