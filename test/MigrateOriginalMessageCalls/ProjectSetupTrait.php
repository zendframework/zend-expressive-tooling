<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\MigrateOriginalMessageCalls;

use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Zend\Stdlib\ConsoleHelper;

trait ProjectSetupTrait
{
    public function setupSrcDir($dir)
    {
        $base = realpath(__DIR__ . '/TestAsset') . DIRECTORY_SEPARATOR;
        $rdi = new RecursiveDirectoryIterator($base . 'src');
        $rii = new RecursiveIteratorIterator($rdi);

        foreach ($rii as $file) {
            if (! $this->isPhpFile($file)) {
                continue;
            }

            $filename = $file->getRealPath();
            $contents = file_get_contents($filename);
            $name = strtr($filename, [$base => '', DIRECTORY_SEPARATOR => '/']);
            vfsStream::newFile($name)
                ->at($dir)
                ->setContent($contents);
        }
    }

    public function isPhpFile(SplFileInfo $file)
    {
        return $file->isFile()
            && $file->getExtension() === 'php'
            && $file->isReadable()
            && $file->isWritable();
    }

    public function setupConsoleHelper()
    {
        $console = $this->prophesize(ConsoleHelper::class);

        $console
            ->writeLine(Argument::containingString('src/FileContainingOriginalRequest.php'))
            ->shouldBeCalled();
        $console
            ->writeLine(Argument::containingString('src/FileContainingOriginalUri.php'))
            ->shouldBeCalled();
        $console
            ->writeLine(Argument::containingString(
                'src/subdir/FileContainingOriginalResponse.php contains one or more getOriginalResponse() calls'
            ))
            ->shouldBeCalled();
        $console
            ->writeLine(Argument::containingString('src/subdir/nested/FileContainingManyStatements.php'))
            ->shouldBeCalled();
        $console
            ->writeLine(Argument::containingString(
                'src/subdir/nested/FileContainingManyStatements.php contains one or more getOriginalResponse() calls'
            ))
            ->shouldBeCalled();

        return $console;
    }
}
