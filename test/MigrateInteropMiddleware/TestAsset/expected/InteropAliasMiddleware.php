<?php

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware\TestAsset;

use Interop\Http\Server\RequestHandlerInterface as Handler;
use Interop\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;

class InteropAliasMiddleware implements ServerMiddleware
{
    public function process(ServerRequestInterface $request, Handler $handler) : Response
    {
        // do something
    }
}
