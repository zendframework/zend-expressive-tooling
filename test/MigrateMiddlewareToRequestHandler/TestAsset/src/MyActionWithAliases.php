<?php

namespace ZendTest\Expressive\Tooling\MigrateMiddlewareToRequestHandler\TestAsset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Zend\Diactoros\Response;

class MyActionWithAliases implements Middleware
{
    public function process(ServerRequestInterface $request, Handler $handler) : ResponseInterface
    {
        return new Response();
    }
}
