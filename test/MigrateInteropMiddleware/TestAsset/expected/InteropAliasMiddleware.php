<?php

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware\TestAsset;

use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;

class InteropAliasMiddleware implements ServerMiddleware
{
    public function process(ServerRequestInterface $request, Handler $handler) : Response
    {
        // do something
    }
}
