<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Command\ScanForErrorMiddleware;

use Zend\Stdlib\ConsoleHelper;

class Command
{
    const DEFAULT_SRC = '/src';

    const TEMPLATE_RESPONSE_DETAILS = <<< 'EOT'
The files listed should be converted to normal middleware. These should
include a try/catch block around a call to $next(), catching
domain-specific exceptions and re-throwing all others:

    try {
    } catch (SomeSpecificException $e) {
        // handle this exception type
    } catch (\Throwable $e) {
        // PHP 7
        throw ($e);
    } catch (\Exception $e) {
        // PHP 5 fallback
        throw ($e);
    }

Any middleware that calls $next() with the third "$err" argument should
be updated to raise an exception instead.
EOT;

    /**
     * @var string Path from which to resolve default src directory
     */
    public $pwd = '.';

    /**
     * @var ConsoleHelper
     */
    private $console;

    /**
     * @var string[]
     */
    private $helpOptions = ['-h', '--help', 'help'];

    /**
     * @param string $command Name of script invoking the command.
     * @param ConsoleHelper $console
     */
    public function __construct($command, ConsoleHelper $console)
    {
        $this->command = $command;
        $this->console = $console;
    }

    /**
     * @param array $args
     * @return int
     */
    public function process(array $args)
    {
        if ($this->isHelpRequest($args)) {
            $help = new Help($this->command, $this->console);
            $help();
            return 0;
        }

        $command = array_shift($args);
        if ($command !== 'scan') {
            $this->console->writeLine('<error>Unknown command</error>', true, STDERR);
            $help = new Help($this->command, $this->console);
            $help(STDERR);
            return 1;
        }

        try {
            $src = $this->getSrcDir($args);
        } catch (ArgvException $e) {
            $this->console->writeLine(sprintf(
                '<error>Unable to determine source directory: %s</error>',
                $e->getMessage()
            ), true, STDERR);
            $help = new Help($this->command, $this->console);
            $help(STDERR);
            return 1;
        }

        $this->console->writeLine('<info>Scanning for error middleware or error middleware invocation...</info>');
        $this->console->writeLine('');

        $scanner = new Scanner($src, $this->console);
        $scanner->scan();

        if (count($scanner) !== 0) {
            $this->console->writeLine('');
            $this->console->writeLine(sprintf(
                '<error>%d file%s contained error middleware or called error middleware.</error>',
                count($scanner),
                count($scanner) > 1 ? 's' : ''
            ));
            $this->console->writeLine('');
            $this->console->writeLine('<info>Check the above logs to determine which files need attention.</info>');
            $this->console->writeLine('');
            $this->console->writeLine(self::TEMPLATE_RESPONSE_DETAILS);
            $this->console->writeLine('');
        }

        $this->console->writeLine('<info>Done!</info>');

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
        if (0 === count($args)) {
            return true;
        }

        $first = array_shift($args);

        if (in_array($first, array_merge($this->helpOptions, ['help']), true)) {
            return true;
        }

        foreach ($this->helpOptions as $option) {
            if (in_array($option, $args, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $args
     * @return string
     * @throws ArgvException
     */
    private function getSrcDir(array $args)
    {
        if (! count($args)) {
            return $this->pwd . self::DEFAULT_SRC;
        }

        if ('--dir' !== array_shift($args)) {
            throw new ArgvException('Invalid options provided');
        }

        if (! count($args)) {
            throw new ArgvException('--dir was missing an argument indicating the path');
        }

        $path = array_shift($args);

        if (! is_dir($path)
            || ! is_readable($path)
        ) {
            throw new ArgvException(sprintf(
                'Invalid --dir argument "%s"; directory does not exist or is not readable',
                $path
            ));
        }

        return $path;
    }
}
