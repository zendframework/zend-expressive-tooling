<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Factory;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\Factory\ConfigFileNotWritableException;

class ConfigFileNotWritableExceptionTest extends TestCase
{
    public function testForFileGeneratesExpectedException()
    {
        $e = ConfigFileNotWritableException::forFile(__FILE__);
        $this->assertInstanceOf(ConfigFileNotWritableException::class, $e);
        $this->assertStringContainsString(sprintf('file "%s"', __FILE__), $e->getMessage());
    }
}
