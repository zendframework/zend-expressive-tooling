<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling;

use Zend\Stdlib\ConsoleHelper;

class ExpressiveHelp
{
    const TEMPLATE = <<< 'EOT'
<info>Usage:</info>

  %s <command>

<info>Commands:</info>

  <info>help</info>                         Display this help/usage message
  <info>create-middleware</info>            Create a middleware class file
  <info>migrate-original-messages</info>    Create a middleware class file
  <info>module</info>                       Create, register, and deregister modules
  <info>pipeline-from-config</info>         Create programmatic pipelines from config
  <info>scan-for-error-middleware</info>    Scan for legacy error middleware

You may also use:

  %s help <command>

in order to obtain help and options for any individual command.
EOT;

    /**
     * @var string
     */
    private $command;

    /**
     * @var ConsoleHelper
     */
    private $helper;

    /**
     * @param string $command Name of script invoking the command.
     * @param ConsoleHelper $helper
     */
    public function __construct($command, ConsoleHelper $helper)
    {
        $this->command = $command;
        $this->helper = $helper;
    }

    /**
     * Emit the help message.
     *
     * @param resource $resource Stream to which to write; defaults to STDOUT.
     * @return void
     */
    public function __invoke($resource = STDOUT)
    {
        // Find relative command path
        $command = strtr(realpath($this->command) ?: $this->command, [
            getcwd() . DIRECTORY_SEPARATOR => '',
            'zendframework' . DIRECTORY_SEPARATOR . 'zend-expressive-tooling' . DIRECTORY_SEPARATOR => '',
        ]);

        $this->helper->writeLine(sprintf(
            self::TEMPLATE,
            $command,
            $command
        ), true, $resource);
    }
}
