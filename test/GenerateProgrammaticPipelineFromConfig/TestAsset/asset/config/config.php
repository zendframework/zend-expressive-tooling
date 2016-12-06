<?php
use Zend\Expressive\Application;
use Zend\Expressive\Helper;

return [
    'middleware_pipeline' => [
        'always' => [
            'middleware' => [
                // Add more middleware here that you want to execute on
                // every request:
                // - bootstrapping
                // - pre-conditions
                // - modifications to outgoing responses
                Helper\ServerUrlMiddleware::class,
                \App\Middleware\XClacksOverhead::class,
            ],
            'priority' => 10000,
        ],

        'api' => [
            'path' => '/api',
            'middleware' => [
                \Api\Middleware\Authentication::class,
                \Api\Middleware\Authorization::class,
                \Api\Middleware\Negotiation::class,
                \Api\Middleware\Validation::class,
            ],
        ],

        'routing' => [
            'middleware' => [
                Application::ROUTING_MIDDLEWARE,
                Helper\UrlHelperMiddleware::class,
                // Add more middleware here that needs to introspect the routing
                // results; this might include:
                // - route-based authentication
                // - route-based validation
                // - etc.
                Application::DISPATCH_MIDDLEWARE,
            ],
            'priority' => 1,
        ],

        'not-found' => [
            'middleware' => \App\Middleware\NotFoundHandler::class,
            'priority' => -1,
        ],

        'error' => [
            'middleware' => \App\Middleware\ErrorMiddleware::class,
            'error' => true,
            'priority' => -10000,
        ]
    ],
    'routes' => [
        [
            'name' => 'home',
            'path' => '/',
            'middleware' => App\Action\HomePageAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'api.posts',
            'path' => '/api/posts',
            'middleware' => App\Action\PostsAction::class,
            'allowed_methods' => ['GET', 'POST'],
            'options' => [
                'sort' => 'updated',
                'order' => 'desc',
            ],
        ],
        [
            'name' => 'api.rest.post',
            'path' => '/rest/post',
            'middleware' => [
                \Api\Middleware\Authentication::class,
                \Api\Middleware\Authorization::class,
                \Api\Middleware\Negotiation::class,
                \Api\Middleware\Validation::class,
                \Api\Action\PostAction::class,
            ],
            'options' => [
                'sort' => 'updated',
                'order' => 'desc',
            ],
        ],
    ],
];
