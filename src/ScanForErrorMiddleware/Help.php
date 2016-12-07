<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\ScanForErrorMiddleware;

use Zend\Stdlib\ConsoleHelper;

class Help
{
    const TEMPLATE = <<< 'EOT'
<info>Usage:</info>

  %s <command> [options]

<info>Commands:</info>

  <info>help</info>             Display this help/usage message
  <info>scan</info>             Scan for error middleware in the given directory

<info>Options:</info>

  <info>--help|-h</info>        Display this help/usage message
  <info>--dir</info>            Specify a path to scan; defaults to src/.

Scans the directory provided by --dir (defaulting to src/) for classes
that either implement Zend\Stratigility\ErrorMiddlewareInterface,
or which implement __invoke() using that signature. Any that are
discovered are reported to the console.
  
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
            $command
        ), true, $resource);
    }
}
