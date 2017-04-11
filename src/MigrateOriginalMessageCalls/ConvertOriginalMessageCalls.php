<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\MigrateOriginalMessageCalls;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertOriginalMessageCalls
{
    /**
     * Regex for matching variable names
     * @var string
     */
    const REGEX_VARNAME = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

    /**
     * @var OutputInterface
     */
    private $console;

    /**
     * @var int
     */
    private $originalResponseCount = 0;

    /**
     * @param OutputInterface $console
     */
    public function __construct(OutputInterface $console)
    {
        $this->console = $console;
    }

    /**
     * Process a directory
     *
     * @param string $directory Directory to process.
     * @return void
     */
    public function process($directory)
    {
        $rdi = new RecursiveDirectoryIterator($directory);
        $rii = new RecursiveIteratorIterator($rdi);

        foreach ($rii as $file) {
            if (! $this->isPhpFile($file)) {
                continue;
            }

            $this->processFile((string) $file);
        }
    }

    /**
     * @return bool
     */
    public function originalResponseFound()
    {
        return $this->originalResponseCount > 0;
    }

    /**
     * Process a single file
     *
     * @param string $filename
     * @return void
     */
    private function processFile($filename)
    {
        $original = file_get_contents($filename);

        if ($this->locateOriginalResponse($original)) {
            $this->originalResponseCount += 1;
            $this->console->writeln(sprintf(
                '<error>File %s contains one or more getOriginalResponse() calls</error>',
                $filename
            ));
        }

        $contents = $this->convertOriginalRequest($original);
        $contents = $this->convertOriginalUri($contents);

        if ($original === $contents) {
            return;
        }

        $this->console->writeln(sprintf('<info>- Updating %s</info>', $filename));

        file_put_contents($filename, $contents);
    }

    /**
     * Convert `getOriginalRequest()` calls to `getAttribute('originalRequest')`
     *
     * @param string $string
     * @return string
     */
    private function convertOriginalRequest($string)
    {
        return preg_replace(
            '#(\$' . self::REGEX_VARNAME . ')-\>getOriginalRequest\(\)#s',
            '$1->getAttribute(\'originalRequest\', $1)',
            $string
        );
    }

    /**
     * Convert `getOriginalUri()` calls to `getAttribute('originalUri')`
     *
     * @param string $string
     * @return string
     */
    private function convertOriginalUri($string)
    {
        return preg_replace(
            '#(\$' . self::REGEX_VARNAME . ')-\>getOriginalUri\(\)#s',
            '$1->getAttribute(\'originalUri\', $1->getUri())',
            $string
        );
    }

    /**
     * Determine if the file contains a getOriginalResponse() call.
     *
     * @param string $string
     * @return bool
     */
    private function locateOriginalResponse($string)
    {
        return strpos($string, 'getOriginalResponse()') !== false;
    }

    /**
     * Is the file a PHP file?
     *
     * @param SplFileInfo $file
     * @return bool
     */
    private function isPhpFile(SplFileInfo $file)
    {
        return $file->isFile()
            && $file->getExtension() === 'php'
            && $file->isReadable()
            && $file->isWritable();
    }
}
