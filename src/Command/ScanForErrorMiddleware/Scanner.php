<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Command\ScanForErrorMiddleware;

use Countable;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Zend\Stdlib\ConsoleHelper;
use Zend\Stratigility\ErrorMiddlewareInterface;

class Scanner implements Countable, IteratorAggregate
{
    /**
     * @var ConsoleHelper
     */
    private $console;

    /**
     * @var int Count of files with error middleware.
     */
    private $count = 0;

    /**
     * @var string Path to scan.
     */
    private $path;

    /**
     * @param string path
     * @param ConsoleHelper $console
     * @throws ScannerException if the path is not a directory or not readable.
     */
    public function __construct($path, ConsoleHelper $console)
    {
        $this->path    = $path;
        $this->console = $console;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * @return ErrorMiddlewareFilter
     */
    public function getIterator()
    {
        $rdi = new RecursiveDirectoryIterator($this->path);
        $rii = new RecursiveIteratorIterator($rdi);
        return new ErrorMiddlewareFilter($rii);
    }

    /**
     * Recursively scan the given path for error middleware.
     *
     * @return void
     */
    public function scan()
    {
        foreach ($this as $file) {
            $this->implementsInterface($file);
            $this->isInvokableMiddleware($file);
            $this->callsNextWithError($file);
        }
    }

    /**
     * Emit info if the file contains a class implementing ErrorMiddlewareInterface
     *
     * @param SplFileInfo $file
     */
    private function implementsInterface(SplFileInfo $file)
    {
        if (empty($file->implementsInterface)) {
            return;
        }
        $this->count += 1;
        $this->console->writeLine(sprintf(
            '- File <info>%s</info> contains a class <error>implementing %s</error>',
            (string) $file,
            ErrorMiddlewareInterface::class
        ));
    }

    /**
     * Emit info if the file contains a class implementing invokable error middleware
     *
     * @param SplFileInfo $file
     */
    private function isInvokableMiddleware(SplFileInfo $file)
    {
        if (empty($file->invokableErrorMiddleware)) {
            return;
        }
        $this->count += 1;
        $this->console->writeLine(sprintf(
            '- File <info>%s</info> MAY contain a class <error>implementing invokable error middleware</error>',
            (string) $file
        ));
    }

    /**
     * Emit info if the file contains a call to $next with an error argument
     *
     * @param SplFileInfo $file
     */
    private function callsNextWithError(SplFileInfo $file)
    {
        if (empty($file->callsNextWithError)) {
            return;
        }
        $this->count += 1;
        $this->console->writeLine(sprintf(
            '- File <info>%s</info> MAY contain a <error>call to $next with an error argument</error>',
            (string) $file
        ));
    }
}
