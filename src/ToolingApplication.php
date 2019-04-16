<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);
namespace Zend\Expressive\Tooling;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class ToolingApplication extends Application
{
    const DEFAULT_NAME = 'expressive';
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container = null, string $version = 'UNKNOWN', string $name = self::DEFAULT_NAME)
    {
        parent::__construct($name, $version);
        $this->container = $container;
        $this->addCommands([
            new Factory\CreateFactoryCommand('factory:create'),
            new CreateMiddleware\CreateMiddlewareCommand('middleware:create'),
            new MigrateInteropMiddleware\MigrateInteropMiddlewareCommand('migrate:interop-middleware'),
            new MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommand(
                'migrate:middleware-to-request-handler'
            ),
            new Module\CreateCommand('module:create'),
            new Module\DeregisterCommand('module:deregister'),
            new Module\RegisterCommand('module:register'),
        ]);

        if (null !== $container) {
            $this->addCommands([
                new CreateHandler\CreateHandlerCommand('action:create'),
                new CreateHandler\CreateHandlerCommand('handler:create')
            ]);
        }
    }
}