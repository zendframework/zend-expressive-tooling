<?php

namespace ZendTest\Expressive\Tooling\MigrateMiddlewareToRequestHandler\TestAsset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Zend\Diactoros\Response\JsonResponse;

class MultipleInterfacesMiddleware implements
    MyInterface,
    MiddlewareInterface,
    SomeInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface {
        return new JsonResponse(['status' => 1]);
    }
}
