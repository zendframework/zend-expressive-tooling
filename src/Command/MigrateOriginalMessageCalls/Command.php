<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2016-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Command\MigrateOriginalMessageCalls;

use Zend\Stdlib\ConsoleHelper;

class Command
{
    const DEFAULT_SRC = '/src';

    const TEMPLATE_RESPONSE_DETAILS = <<< 'EOT'
To correct these files, look for a request argument, and update the
following calls ($response may be a different variable):

    $response->getOriginalResponse()

to:

    $request->getAttribute('originalResponse', $response)

($request may be a different variable)
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
    private $helpOptions = ['-h', '--help'];

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
                '<error>Unable to determine src directory: %s</error>',
                $e->getMessage()
            ), true, STDERR);
            $help = new Help($this->command, $this->console);
            $help(STDERR);
            return 1;
        }

        $converter = new ConvertOriginalMessageCalls($this->console);
        $converter->process($src);

        if ($converter->originalResponseFound()) {
            $this->console->writeLine('<error>One or more files contained calls to getOriginalResponse().</error>');
            $this->console->writeLine('<info>Check the above logs to determine which files need attention.</info>');
            $this->console->writeLine(self::TEMPLATE_RESPONSE_DETAILS);
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

        if ('--src' !== array_shift($args)) {
            throw new ArgvException('Invalid options provided');
        }

        if (! count($args)) {
            throw new ArgvException('--src was missing an argument indicating the path');
        }

        $path = array_shift($args);

        if (! is_dir($path)) {
            throw new ArgvException(sprintf(
                'Invalid --src argument "%s"; directory does not exist',
                $path
            ));
        }

        return $path;
    }
}
