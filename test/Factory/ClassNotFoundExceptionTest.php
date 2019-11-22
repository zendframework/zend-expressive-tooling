<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\Factory;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\Factory\ClassNotFoundException;

class ClassNotFoundExceptionTest extends TestCase
{
    public function testForClassNameGeneratesExpectedException()
    {
        $e = ClassNotFoundException::forClassName(__CLASS__);
        $this->assertInstanceOf(ClassNotFoundException::class, $e);
        $this->assertStringContainsString(sprintf('Class "%s"', __CLASS__), $e->getMessage());
    }
}
