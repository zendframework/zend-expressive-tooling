<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\CreateMiddleware;

use Zend\Stdlib\ConsoleHelper;

class Command
{
    const DEFAULT_COMMAND_NAME = 'expressive-create-middleware';

    public $projectDir = '.';

    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $helpArgs = ['--help', '-h', 'help'];

    /**
     * @var ConsoleHelper
     */
    private $console;

    /**
     * @param string $command Script that is invoking the command.
     * @param null|ConsoleHelper $console
     */
    public function __construct($command = self::DEFAULT_COMMAND_NAME, ConsoleHelper $console = null)
    {
        $this->command = (string) $command;
        $this->console = $console ?: new ConsoleHelper();
    }

    /**
     * @param array $args
     * @return int
     */
    public function process(array $args)
    {
        if ($this->isHelpRequest($args)) {
            $help = new Help($this->command, $this->console);
            $help(STDOUT);
            return 0;
        }

        $args = $this->filterArgs($args);
        $middleware = array_shift($args);

        $this->console->writeLine(sprintf('<info>Creating middleware %s...</info>', $middleware));

        $generator = new CreateMiddleware();

        try {
            $path = $generator->process($middleware, $this->projectDir);
        } catch (CreateMiddlewareException $e) {
            $this->console->writeLine('<error>Error during generation:</error>', true, STDERR);
            $this->console->writeLine(sprintf('  <error>%s</error>', $e->getMessage()), true, STDERR);
            return 1;
        }

        $this->console->writeLine('<info>Success!</info>');
        $this->console->writeLine(sprintf(
            '<info>- Created class %s, in file %s</info>',
            $middleware,
            $path
        ));

        return 0;
    }

    /**
     * Is this a help request?
     *
     * @param array $args
     * @return bool
     */
    private function isHelpRequest(array $args)
    {
        $numArgs = count($args);
        if (0 === $numArgs) {
            return true;
        }

        $arg = array_shift($args);

        if (in_array($arg, $this->helpArgs, true)) {
            return true;
        }

        if (empty($args)) {
            return false;
        }

        $arg = array_shift($args);

        return in_array($arg, $this->helpArgs, true);
    }

    /**
     * @param array $args
     * @return array
     */
    private function filterArgs(array $args)
    {
        return array_filter($args, function ($arg) {
            return ! in_array($arg, $this->helpArgs, true);
        });
    }
}
