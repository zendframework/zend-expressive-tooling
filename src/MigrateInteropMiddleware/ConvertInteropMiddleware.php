<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\MigrateInteropMiddleware;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertInteropMiddleware
{
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function process(string $directory)
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

        $delegate = null;
        if (preg_match(
            '#use\s+Interop\\\\Http\\\\ServerMiddleware\\\\DelegateInterface(\s*)(;|as\s*([^; ]+)\s*;)#',
            $contents,
            $matches
        )) {
            $delegate = $matches[2] === ';' ? 'DelegateInterface' : $matches[3];

            $replacement = $matches[2] === ';'
                ? ' as DelegateInterface;'
                : $matches[1] . $matches[2];

            $contents = str_replace(
                $matches[0],
                'use Interop\\Http\\Server\\RequestHandlerInterface' . $replacement,
                $contents
            );
        }

        $middleware = null;
        if (preg_match(
            '#use\s+Interop\\\\Http\\\\ServerMiddleware\\\\MiddlewareInterface(\s*)(;|as\s*([^;\s]+)\s*;)#',
            $contents,
            $matches
        )) {
            $middleware = $matches[2] === ';' ? 'MiddlewareInterface' : $matches[3];

            $contents = str_replace(
                $matches[0],
                'use Interop\\Http\\Server\\MiddlewareInterface' . $matches[1] . $matches[2],
                $contents
            );
        }

        // if delegate, class implements delegate interface and has process method
        if ($delegate
            && preg_match('#class\s+[^{]+?implements\s*([^{]+?,\s*)*' . $delegate . '(\s|,|{)#i', $contents)
            && preg_match('#public\s+function\s+process\s*\([^\)]+?\)\s*(:?)#', $contents, $matches)
        ) {
            $replacement = str_replace('process', 'handle', $matches[0]);
            if ($matches[1] !== ':') {
                $ri = $this->getResponseInterface($contents);
                $replacement = preg_replace('#\)#', ') : ' . $ri, $replacement);
            }

            $contents = str_replace($matches[0], $replacement, $contents);
        }

        // is middleware, class implements middleware interface and has process method
        if ($middleware
            && preg_match('#class\s+[^{]+?implements\s*([^{]+?,\s*)*' . $middleware . '(\s|,|{)#i', $contents)
            && preg_match('#public\s+function\s+process\(\s*.+?,\s*.+?\s+(\$.+?)\s*\)\s*{#', $contents, $matches)
        ) {
            $ri = $this->getResponseInterface($contents);
            $replacement = preg_replace('#\)#', ') : ' . $ri, $matches[0]);

            $contents = str_replace($matches[0], $replacement, $contents);
            $preg = '/' . preg_quote($matches[1], '\\') . '\s*->\s*process\(/';
            $contents = preg_replace($preg, $matches[1] . '->handle(', $contents);
        }

        if ($original === $contents) {
            return;
        }

        $this->output->writeln(sprintf('<info>- Updating %s</info>', $filename));

        file_put_contents($filename, $contents);
    }

    private function getResponseInterface(string $content)
    {
        $responseInterface = null;
        if (preg_match(
            '#use\s*Psr\\\\Http\\\\Message\\\\ResponseInterface\s*(;|as\s*([^;\s]+)\s*;)#',
            $content,
            $matches
        )) {
            if ($matches[1] === ';') {
                return 'ResponseInterface';
            }

            return $matches[2];
        }

        return '\Psr\Http\Message\ResponseInterface';
    }
}
