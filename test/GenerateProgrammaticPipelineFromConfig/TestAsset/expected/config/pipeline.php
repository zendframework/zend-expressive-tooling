<?php
/**
 * Expressive middleware pipeline
 */

/** @var \Zend\Expressive\Application $app */
$app->pipe(\Zend\Stratigility\Middleware\OriginalMessages::class);
$app->pipe(\Zend\Stratigility\Middleware\ErrorHandler::class);
$app->pipe('Zend\\Expressive\\Helper\\ServerUrlMiddleware');
$app->pipe('App\\Middleware\\XClacksOverhead');
$app->pipe('/api', [
    'Api\\Middleware\\Authentication',
    'Api\\Middleware\\Authorization',
    'Api\\Middleware\\Negotiation',
    'Api\\Middleware\\Validation',
]);
$app->pipeRoutingMiddleware();
$app->pipe('Zend\\Expressive\\Helper\\UrlHelperMiddleware');
$app->pipeDispatchMiddleware();
$app->pipe('App\\Middleware\\NotFoundHandler');
$app->pipeErrorHandler('App\\Middleware\\ErrorMiddleware');
$app->pipe(\Zend\Expressive\Middleware\NotFoundHandler::class);
