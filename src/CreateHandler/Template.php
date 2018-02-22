<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

/**
 * Value object representing details of a generated template.
 */
class Template
{
    /** @var string */
    private $name;

    /** @var string */
    private $path;

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPath() : string
    {
        return $this->path;
    }
}
