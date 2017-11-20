<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\MigrateOriginalMessageCalls;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

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
        $console = $this->prophesize(OutputInterface::class);

        $console
            ->writeln(Argument::containingString('src/FileContainingOriginalRequest.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString('src/FileContainingOriginalUri.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString(
                'src/subdir/FileContainingOriginalResponse.php contains one or more getOriginalResponse() calls'
            ))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString('src/subdir/nested/FileContainingManyStatements.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString(
                'src/subdir/nested/FileContainingManyStatements.php contains one or more getOriginalResponse() calls'
            ))
            ->shouldBeCalled();

        return $console;
    }

    public function assertExpected(string $dir)
    {
        $base = $dir;
        $rdi = new RecursiveDirectoryIterator($dir);
        $rii = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if (! $this->isPhpFile($file)) {
                continue;
            }

            $filename = $file->getPathname();
            $content = file_get_contents($filename);
            $name = strtr($filename, [$base . '/src' => __DIR__ . '/TestAsset/expected', DIRECTORY_SEPARATOR => '/']);

            Assert::assertSame(file_get_contents($name), $content);
        }
    }
}
