<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Tooling\MigrateInteropMiddleware;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

trait ProjectSetupTrait
{
    private function setupSrcDir($dir) : void
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

    private static function isPhpFile(SplFileInfo $file) : bool
    {
        return $file->isFile()
            && $file->getExtension() === 'php'
            && $file->isReadable()
            && $file->isWritable();
    }

    /**
     * @return OutputInterface|ObjectProphecy
     */
    private function setupConsoleHelper()
    {
        $console = $this->prophesize(OutputInterface::class);

        $console
            ->writeln(Argument::containingString('src/InteropAliasMiddleware.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString('src/MultilineMiddleware.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString('src/MultipleInterfacesMiddleware.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString('src/MyClass.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString('src/MyInteropDelegate.php'))
            ->shouldBeCalled();
        $console
            ->writeln(Argument::containingString('src/MyInteropMiddleware.php'))
            ->shouldBeCalled();

        return $console;
    }

    public static function assertExpected(string $dir) : void
    {
        $base = $dir;
        $rdi = new RecursiveDirectoryIterator($dir);
        $rii = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if (! self::isPhpFile($file)) {
                continue;
            }

            $filename = $file->getPathname();
            $content = file_get_contents($filename);
            $name = strtr($filename, [$base . '/src' => __DIR__ . '/TestAsset/expected', DIRECTORY_SEPARATOR => '/']);

            Assert::assertStringEqualsFile($name, $content);
        }
    }
}
