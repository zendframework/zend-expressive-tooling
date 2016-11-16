<?php
/**
 * Expressive programmatic pipeline configuration
 */

use Zend\Expressive\Container\ErrorHandlerFactory;
use Zend\Expressive\Container\ErrorResponseGeneratorFactory;
use Zend\Expressive\Container\NotFoundHandlerFactory;
use Zend\Expressive\Middleware\ErrorResponseGenerator;
use Zend\Expressive\Middleware\NotFoundHandler;
use Zend\Stratigility\Middleware\ErrorHandler;

return [
    'dependencies' => [
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
