<?php
/**
 * Expressive programmatic pipeline configuration
 */

use Zend\Expressive\Container\ErrorHandlerFactory;
use Zend\Expressive\Container\ErrorResponseGeneratorFactory;
use Zend\Expressive\Container\NotFoundHandlerFactory;
use Zend\Expressive\Middleware\ErrorResponseGenerator;
use Zend\Expressive\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Middleware\NotFoundHandler;
use Zend\Stratigility\Middleware\ErrorHandler;
use Zend\Stratigility\Middleware\OriginalMessages;

return [
    'dependencies' => [
        'invokables' => [
            ImplicitHeadMiddleware::class => ImplicitHeadMiddleware::class,
            ImplicitOptionsMiddleware::class => ImplicitOptionsMiddleware::class,
            OriginalMessages::class => OriginalMessages::class,
        ],
        'factories' => [
            ErrorHandler::class => ErrorHandlerFactory::class,
            // Override the following in a local config file to use
            // Zend\Expressive\Container\WhoopsErrorResponseGeneratorFactory
            // in order to use Whoops for development error handling.
            ErrorResponseGenerator::class => ErrorResponseGeneratorFactory::class,
            NotFoundHandler::class => NotFoundHandlerFactory::class,
        ],
    ],
    'zend-expressive' => [
        'programmatic_pipeline' => true,
        'raise_throwables'      => true,
    ],
];
