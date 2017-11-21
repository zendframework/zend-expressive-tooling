<?php

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware\TestAsset;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;

class MyInteropDelegate implements DelegateInterface
{
    public function process(ServerRequestInterface $request)
    {
    }
}
