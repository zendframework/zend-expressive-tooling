<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig;

use ArrayObject;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;
use Zend\Expressive\Application;
use Zend\Expressive\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Stdlib\ConsoleHelper;
use Zend\Stdlib\SplPriorityQueue;

class Generator implements Constants
{
    const TEMPLATE_CONFIG = <<< 'EOT'
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
use Zend\Expressive\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Middleware\NotFoundHandler;
use Zend\Stratigility\Middleware\ErrorHandler;
use Zend\Stratigility\Middleware\OriginalMessages;

return [
    'dependencies' => [
        'aliases' => [
            // Override the following to provide an alternate default delegate.
            'Zend\Expressive\Delegate\DefaultDelegate' => NotFoundDelegate::class,
        ],
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
            // Override the following to use an alternate "not found" delegate.
            NotFoundDelegate::class => NotFoundDelegateFactory::class,
            NotFoundHandler::class => NotFoundHandlerFactory::class,
        ],
    ],
    'zend-expressive' => [
        'programmatic_pipeline' => true,
        'raise_throwables'      => true,
    ],
];

EOT;

    const TEMPLATE_PIPELINE = <<< 'EOT'
<?php
/**
 * Expressive middleware pipeline
 */

/** @var \Zend\Expressive\Application $app */
%s

EOT;

    const TEMPLATE_ROUTES = <<< 'EOT'
<?php
/**
 * Expressive routed middleware
 */

/** @var \Zend\Expressive\Application $app */
%s

EOT;

    // @codingStandardsIgnoreStart
    const TEMPLATE_PIPELINE_NO_PATH = '$app->pipe(%s);';

    const TEMPLATE_PIPELINE_WITH_PATH = '$app->pipe(%s, %s);';

    const TEMPLATE_ROUTED_METHOD_NO_NAME = '$app->%s(\'%s\', %s)';

    const TEMPLATE_ROUTED_METHOD_WITH_NAME = '$app->%s(\'%s\', %s, \'%s\')';

    const TEMPLATE_ROUTED_NO_METHOD_NO_NAME = '$app->route(\'%s\', %s, null)';

    const TEMPLATE_ROUTED_NO_METHOD_WITH_NAME = '$app->route(\'%s\', %s, null, \'%s\')';

    const TEMPLATE_ROUTED_METHODS_NO_NAME = '$app->route(\'%s\', %s, %s)';

    const TEMPLATE_ROUTED_METHODS_WITH_NAME = '$app->route(\'%s\', %s, %s, \'%s\')';
    // @codingStandardsIgnoreEnd

    /**
     * @var string Root path against which paths are relative.
     */
    private $projectDir;

    /**
     * @var ConsoleHelper
     */
    private $console;

    /**
     * @param OutputInterface $console
     */
    public function __construct(OutputInterface $console, $projectDir = '.')
    {
        $this->console = $console;
        $this->projectDir = $projectDir;
    }

    /**
     * @param string $configFile
     * @throws GeneratorException
     */
    public function process($configFile)
    {
        $this->detectPreviousRun();

        $config = $this->readConfigFile($configFile);

        $pipeline = isset($config['middleware_pipeline']) && is_array($config['middleware_pipeline'])
            ? $config['middleware_pipeline']
            : [];

        $routes = isset($config['routes']) && is_array($config['routes'])
            ? $config['routes']
            : [];

        file_put_contents(
            $this->projectDir . self::PATH_PIPELINE,
            sprintf(self::TEMPLATE_PIPELINE, $this->generatePipeline($pipeline))
        );

        file_put_contents(
            $this->projectDir . self::PATH_ROUTES,
            sprintf(self::TEMPLATE_ROUTES, $this->generateRoutes($routes))
        );

        file_put_contents($this->projectDir . self::PATH_CONFIG, self::TEMPLATE_CONFIG);

        $this->updateApplication();
    }

    /**
     * @param string $configFile
     * @return array
     * @throws GeneratorException
     */
    private function readConfigFile($configFile)
    {
        if (! file_exists($configFile) || ! is_readable($configFile)) {
            throw new GeneratorException(sprintf(
                'Config file "%s" not found or not readable',
                $configFile
            ));
        }

        set_error_handler(function ($errno, $errstr) use ($configFile) {
            throw new GeneratorException(sprintf(
                'Error reading config file "%s": %s',
                $configFile,
                $errstr
            ));
        }, E_WARNING);

        $config = include $configFile;

        if ($config instanceof ArrayObject) {
            $config = $config->getArrayCopy();
        }

        if (! is_array($config)) {
            throw new GeneratorException(sprintf(
                'Config file "%s" did not return an array!',
                $configFile
            ));
        }

        return $config;
    }

    /**
     * @param array $config
     * @return string
     */
    private function generatePipeline(array $config)
    {
        $pipeline = [];

        foreach ($this->generatePriorityQueueFromConfig($config) as $spec) {
            if (empty($spec) || empty($spec['middleware'])) {
                continue;
            }

            if (empty($spec['path'])
                && $spec['middleware'] === Application::ROUTING_MIDDLEWARE
            ) {
                $pipeline[] = '$app->pipeRoutingMiddleware();';
                $pipeline[] = sprintf(
                    self::TEMPLATE_PIPELINE_NO_PATH,
                    $this->formatMiddleware(ImplicitHeadMiddleware::class)
                );
                $pipeline[] = sprintf(
                    self::TEMPLATE_PIPELINE_NO_PATH,
                    $this->formatMiddleware(ImplicitOptionsMiddleware::class)
                );
                continue;
            }

            if (empty($spec['path'])
                && $spec['middleware'] === Application::DISPATCH_MIDDLEWARE
            ) {
                $pipeline[] = '$app->pipeDispatchMiddleware();';
                continue;
            }

            $path       = isset($spec['path']) ? (string) $spec['path'] : null;
            $middleware = $this->formatMiddleware($spec['middleware']);
            $error      = isset($spec['error']) ? (bool) $spec['error'] : false;

            if ($error) {
                $this->console->writeln(sprintf(
                    '<error>Encountered error middleware "%s"; did not add to pipeline</error>',
                    $middleware
                ));
                continue;
            }

            $pipeline[] = (null === $path)
                ? sprintf(self::TEMPLATE_PIPELINE_NO_PATH, $middleware)
                : sprintf(self::TEMPLATE_PIPELINE_WITH_PATH, $this->createOptionValue($path), $middleware);
        }

        // Push the original messages middleware and error handler to the top
        // of the pipeline, and the not-found handler to the end.
        array_unshift($pipeline, '$app->pipe(\Zend\Stratigility\Middleware\ErrorHandler::class);');
        array_unshift($pipeline, '$app->pipe(\Zend\Stratigility\Middleware\OriginalMessages::class);');
        array_push($pipeline, '$app->pipe(\Zend\Expressive\Middleware\NotFoundHandler::class);');

        return implode("\n", $pipeline);
    }

    /**
     * @param array $config
     * @return string
     */
    private function generateRoutes(array $config)
    {
        $routes = [];
        foreach ($config as $spec) {
            $route = $this->getRoute($spec);

            if (! isset($spec['options']) || ! is_array($spec['options'])) {
                $routes[] = $route . ';';
                continue;
            }

            $routes[] = $route . sprintf(
                "\n    ->setOptions(%s);",
                $this->formatOptions($spec['options'], 2)
            );
        }

        return implode("\n", $routes);
    }

    /**
     * @param array $spec
     * @return string
     */
    private function getRoute(array $spec)
    {
        $middleware = $this->formatMiddleware($spec['middleware']);
        $path       = $spec['path'];

        if (! isset($spec['allowed_methods'])
            || $spec['allowed_methods'] === Route::HTTP_METHOD_ANY
        ) {
            return empty($spec['name'])
                ? sprintf(self::TEMPLATE_ROUTED_NO_METHOD_NO_NAME, $path, $middleware)
                : sprintf(self::TEMPLATE_ROUTED_NO_METHOD_WITH_NAME, $path, $middleware, $spec['name']);
        }

        if (count($spec['allowed_methods']) === 1) {
            $method = strtolower(array_shift($spec['allowed_methods']));

            return empty($spec['name'])
                ? sprintf(self::TEMPLATE_ROUTED_METHOD_NO_NAME, $method, $path, $middleware)
                : sprintf(self::TEMPLATE_ROUTED_METHOD_WITH_NAME, $method, $path, $middleware, $spec['name']);
        }

        $methods = sprintf('[%s]', implode(', ', array_map(function ($method) {
            return sprintf("'%s'", $method);
        }, $spec['allowed_methods'])));

        return empty($spec['name'])
            ? sprintf(self::TEMPLATE_ROUTED_METHODS_NO_NAME, $path, $middleware, $methods)
            : sprintf(self::TEMPLATE_ROUTED_METHODS_WITH_NAME, $path, $middleware, $methods, $spec['name']);
    }

    /**
     * @param array $config
     * @return SplPriorityQueue
     */
    private function generatePriorityQueueFromConfig(array $config)
    {
        return array_reduce($config, function ($queue, $spec) {
            $priority = isset($spec['priority']) ? $spec['priority'] : 1;

            if (! is_array($spec['middleware']) || ! empty($spec['path'])) {
                $queue->insert($spec, $priority);
                return $queue;
            }

            // If no path, we should flow these as a single pipeline. To do
            // that, we create additional entries in the queue, one for each
            // middleware in the array.
            foreach ($spec['middleware'] as $middleware) {
                $single = $spec;
                $single['middleware'] = $middleware;
                $queue->insert($single, $priority);
            }

            return $queue;
        }, new SplPriorityQueue());
    }

    /**
     * Format middleware argument(s) for purposes of code generation.
     *
     * @param string|array $middlewareSpec
     * @return string
     * @throws GeneratorException for invalid middleware values.
     */
    private function formatMiddleware($middlewareSpec)
    {
        if (is_string($middlewareSpec)
            || in_array($middlewareSpec, [Application::ROUTING_MIDDLEWARE, Application::DISPATCH_MIDDLEWARE], true)
        ) {
            return $this->createOptionValue($middlewareSpec);
        }

        if (! is_array($middlewareSpec)) {
            throw new GeneratorException(
                'One or more middleware specifications contained non-string, '
                . 'non-array items; cannot process.'
            );
        }

        $middleware = array_map(function ($item) {
            if (! is_string($item)) {
                throw new GeneratorException(
                    'One or more middleware pipelines contained non-string '
                    . 'items; cannot process.'
                );
            }

            return $this->createOptionValue($item);
        }, $middlewareSpec);

        if (count($middleware) === 1) {
            return array_shift($middleware);
        }

        return sprintf("[\n    %s,\n]", implode(",\n    ", $middleware));
    }

    /**
     * @param array|\ArrayObject $options
     * @param int $indentLevel
     * @return string
     */
    private function formatOptions($options, $indentLevel = 1)
    {
        $indent = str_repeat(' ', $indentLevel * 4);
        $entries = [];
        foreach ($options as $key => $value) {
            $key = $this->createOptionKey($key);
            $entries[] = sprintf(
                '%s%s%s,',
                $indent,
                $key ? sprintf('%s => ', $key) : '',
                $this->createOptionValue($value, $indentLevel)
            );
        }

        $outerIndent = str_repeat(' ', ($indentLevel - 1) * 4);

        return sprintf(
            "[\n%s\n%s]",
            implode("\n", $entries),
            $outerIndent
        );
    }

    /**
     * @param string|int|null $key
     * @return null|string
     */
    private function createOptionKey($key)
    {
        if (is_string($key) && class_exists($key)) {
            return sprintf('\\%s::class', $key);
        }

        if (is_int($key)) {
            return null;
        }

        return sprintf("'%s'", $key);
    }

    /**
     * Create a value for generated code.
     *
     * If the value is one of the routing/middleware dispatch constants,
     * a string indicating the constant is returned.
     *
     * If the value is a FQCN, it is returned as a FQCN, with the suffix
     * ::class provided.
     *
     * If all other cases, it is returned as the var_export value.
     *
     * @param mixed $value
     * @param int $indentLevel
     * @return string
     */
    private function createOptionValue($value, $indentLevel = 1)
    {
        if (is_array($value) || $value instanceof Traversable) {
            return $this->formatOptions($value, $indentLevel + 1);
        }

        if (is_string($value)) {
            if ($value === Application::ROUTING_MIDDLEWARE) {
                return '\Zend\Expressive\Application::ROUTING_MIDDLEWARE';
            }

            if ($value === Application::DISPATCH_MIDDLEWARE) {
                return '\Zend\Expressive\Application::DISPATCH_MIDDLEWARE';
            }

            if (class_exists($value)) {
                return sprintf('\\%s::class', $value);
            }
        }

        return var_export($value, true);
    }

    /**
     * Update the application file to include the pipeline and routes.
     *
     * @return void
     * @throws GeneratorException if unable to locate the application
     *     execution directive in the file.
     */
    private function updateApplication()
    {
        $applicationPath = $this->projectDir . self::PATH_APPLICATION;
        $application = file_get_contents($applicationPath);
        $position = strpos($application, '$app->run');

        if (! $position) {
            throw new GeneratorException(wordwrap(
                'Generated config/pipeline.php, config/routes.php, and '
                . 'config/autoload/programmatic-pipeline.global.php, but was '
                . 'unable to update public/index.php; could not find line '
                . 'executing $app->run().',
                72,
                PHP_EOL
            ));
        }

        $updated = substr($application, 0, $position)
            . "require 'config/pipeline.php';\n"
            . "require 'config/routes.php';\n"
            . substr($application, $position);

        file_put_contents($applicationPath, $updated);
    }

    /**
     * Determine if the command has been run previously.
     *
     * @throws GeneratorException when config/pipeline.php and/or
     *     config/routes.php exists.
     */
    private function detectPreviousRun()
    {
        $pipeline = $this->projectDir . self::PATH_PIPELINE;
        $routes   = $this->projectDir . self::PATH_ROUTES;

        if (file_exists($pipeline) || file_exists($routes)) {
            throw new GeneratorException(wordwrap(
                'Unable to generate programmatic pipeline; previous run '
                . 'detected. One of either config/pipeline.php or config/routes.php '
                . 'was discovered. Please check to see if you already have a '
                . 'programmatic pipeline in place, and/or remove these files and '
                . 're-run this command.',
                72,
                PHP_EOL
            ));
        }
    }
}
