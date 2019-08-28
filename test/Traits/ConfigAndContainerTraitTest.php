<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\Traits;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Tooling\ConfigAndContainerTrait;
use ReflectionClass;
use RuntimeException;
use Traversable;

class ConfigAndConatinerTraitTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRootForNormalBehaviour = __DIR__.'/TestAsset/normal/';
        $this->configAndContainer = $this->getMockForTrait(ConfigAndContainerTrait::class);
    }

    protected static function getMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @testCase getConfig should always return an array
     */
    public function testForGetConfigExpectedReturnType()
    {
        $_traitClassName = get_class($this->configAndContainer);
        $getConfig = self::getMethod($_traitClassName, 'getConfig');
        $config = $getConfig->invokeArgs($this->configAndContainer, [$this->projectRootForNormalBehaviour]);
        $this->assertTrue(is_array($config));
    }
    
}
