<?php

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware\TestAsset;

use Interop\Http\ServerMiddleware\DelegateInterface as Handler;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;

class InteropAliasMiddleware implements ServerMiddleware
{
    public function process(ServerRequestInterface $request, Handler $handler)
    {
        // do something
    }
}
