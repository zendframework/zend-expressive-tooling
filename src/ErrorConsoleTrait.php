<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ErrorConsoleTrait
{
    /**
     * Retrieve console output, if possible, to allow writing to STDERR.
     *
     * @param OutputInterface $output
     * @return OutputInterface
     */
    private function getErrorConsole(OutputInterface $output)
    {
        return $output instanceof ConsoleOutputInterface
            ? $output->getErrorOutput()
            : $output;
    }
}
