<?php

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware\TestAsset;

use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ServerRequestInterface;

class MyClass
{
    public function process(ServerRequestInterface $request, Handler $handler)
    {
    }

    public function handle(ServerRequestInterface $request)
    {
    }
}
