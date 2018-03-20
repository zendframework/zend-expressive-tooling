<?php
/**
 * Expressive programmatic pipeline configuration
 */

use Zend\Expressive\Container\ErrorHandlerFactory;
use Zend\Expressive\Container\ErrorResponseGeneratorFactory;
use Zend\Expressive\Container\NotFoundDelegateFactory;
use Zend\Expressive\Container\NotFoundHandlerFactory;
use Zend\Expressive\Delegate\NotFoundDelegate;
use Zend\Expressive\Middleware\ErrorResponseGenerator;
use Zend\Expressive\Middleware\NotFoundHandler;
use Zend\Expressive\Router;
use Zend\Stratigility\Middleware\ErrorHandler;
use Zend\Stratigility\Middleware\OriginalMessages;

return [
    'dependencies' => [
        'aliases' => [
            // Override the following to provide an alternate default delegate.
            'Zend\Expressive\Delegate\DefaultDelegate' => NotFoundDelegate::class,
        ],
        'invokables' => [
            OriginalMessages::class => OriginalMessages::class,
        ],
        'factories' => [
            ErrorHandler::class => ErrorHandlerFactory::class,
            // Override the following in a local config file to use
            // Zend\Expressive\Container\WhoopsErrorResponseGeneratorFactory
            // in order to use Whoops for development error handling.
            ErrorResponseGenerator::class => ErrorResponseGeneratorFactory::class,
            // Override the following to use an alternate "not found" delegate.
            NotFoundDelegate::class => NotFoundDelegateFactory::class,
            NotFoundHandler::class => NotFoundHandlerFactory::class,
            // These are duplicates, in case the zend-expressive-router package ConfigProvider is not wired:
            Router\Middleware\ImplicitHeadMiddleware::class => Router\Middleware\ImplicitHeadMiddlewareFactory::class,
            Router\Middleware\ImplicitOptionsMiddleware::class => Router\Middleware\ImplicitOptionsMiddlewareFactory::class,
        ],
    ],
    'zend-expressive' => [
        'programmatic_pipeline' => true,
        'raise_throwables'      => true,
    ],
];
