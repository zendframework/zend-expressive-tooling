<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\MigrateMiddlewareToRequestHandler;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertMiddleware
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function process(string $directory) : void
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

    private function isPhpFile(SplFileInfo $file) : bool
    {
        return $file->isFile()
            && $file->getExtension() === 'php'
            && $file->isReadable()
            && $file->isWritable();
    }

    private function processFile(string $filename) : void
    {
        $original = file_get_contents($filename);
        $contents = $original;

        if (! preg_match(
            '#use\s+Psr\\\\Http\\\\Server\\\\MiddlewareInterface(\s*)(?<end>;|as\s*(?<alias>[^;\s]+)\s*;)#',
            $contents,
            $matches
        )) {
            return;
        }

        $middleware = $matches['end'] === ';'
            ? 'MiddlewareInterface'
            : $matches['alias'];

        if (! preg_match(
            '#public\s+function\s+process\s*\(.*?,[^)]*(?<var>\$\w+)\s*\)#s',
            $contents,
            $matches
        )) {
            return;
        }

        $var = preg_quote($matches['var'], '#');
        if (preg_match('#' . $var . '\s*->\s*handle\s*\(' . '#', $contents)) {
            $this->output->writeln(sprintf(
                '<comment>- Skipping %s; request handler usage detected</comment>',
                $filename
            ));

            return;
        }

        // Remove imported MiddlewareInterface
        $contents = preg_replace(
            '#use\s+Psr\\\\Http\\\\Server\\\\MiddlewareInterface(.*);\n?#',
            '',
            $contents
        );

        // Change process to handle function and remove 2nd parameter
        $contents = preg_replace(
            '#(public\s+function\s+)(process)(\s*\(.*?)(,.*?)(\s*\))#s',
            '\\1handle\\3\\5',
            $contents
        );

        // Remove alias from imported RequestHandlerInterface
        $contents = preg_replace(
            '#(use\s+Psr\\\\Http\\\\Server\\\\RequestHandlerInterface).*;#',
            '\\1;',
            $contents
        );

        // Change implemented interface on the class from MiddlewareInterface to RequestHandlerInterface
        $contents = preg_replace(
            '#(class\s+.*implements\s+[^{]*,?\s*)' . preg_quote($middleware, '#') . '(,|\s|{)#',
            '\\1RequestHandlerInterface\\2',
            $contents
        );

        if ($original === $contents) {
            return;
        }

        $this->output->writeln(sprintf('<info>- Updating %s</info>', $filename));

        file_put_contents($filename, $contents);
    }
}
