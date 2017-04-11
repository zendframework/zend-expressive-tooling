<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig\Generator;
use Zend\Expressive\Tooling\GenerateProgrammaticPipelineFromConfig\GeneratorException;

class GeneratorTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var Generator */
    private $generator;

    public function setUp()
    {
        $this->dir = vfsStream::setup('project');
        $this->console = $this->prophesize(OutputInterface::class);
        $this->generator = new Generator($this->console->reveal(), vfsStream::url('project'));
    }

    public function generatePipeline()
    {
        vfsStream::newFile('config/config.php')
            ->at($this->dir)
            ->setContent(file_get_contents(__DIR__ . '/TestAsset/asset/config/config.php'));

        vfsStream::newFile('public/index.php')
            ->at($this->dir)
            ->setContent(file_get_contents(__DIR__ . '/TestAsset/asset/public/index.php'));

        vfsStream::newDirectory('config/autoload', 0755)
            ->at($this->dir);
    }

    public function testGeneratesExpectedPipeline()
    {
        $this->generatePipeline();

        $this->console
            ->writeln(Argument::containingString('ErrorMiddleware'))
            ->shouldBeCalled();

        $configFile = vfsStream::url('project/config/config.php');
        $this->generator->process($configFile);

        $pipelineFile = vfsStream::url('project/config/pipeline.php');
        $this->assertFileExists($pipelineFile);
        $this->assertFileEquals(
            __DIR__ . '/TestAsset/expected/config/pipeline.php',
            $pipelineFile,
            'Generated pipeline does not match expected pipeline'
        );

        $routesFile = vfsStream::url('project/config/routes.php');
        $this->assertFileExists($routesFile);
        $this->assertFileEquals(
            __DIR__ . '/TestAsset/expected/config/routes.php',
            $routesFile,
            'Generated routing does not match expected routing'
        );

        $pipelineConfigFile = vfsStream::url('project/config/autoload/programmatic-pipeline.global.php');
        $this->assertFileExists($pipelineConfigFile);
        $this->assertFileEquals(
            __DIR__ . '/TestAsset/expected/config/autoload/programmatic-pipeline.global.php',
            $pipelineConfigFile,
            'Generated pipeline config does not match expected config'
        );

        $this->assertFileEquals(
            __DIR__ . '/TestAsset/expected/public/index.php',
            vfsStream::url('project/public/index.php'),
            'Generated public/index.php does not match expected'
        );
    }

    public function testRaisesExceptionIfConfigFileNotFound()
    {
        $configFile = vfsStream::url('project/config/config.php');

        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('not found');
        $this->generator->process($configFile);
    }

    public function testRaisesExceptionIfConfigFileNotReadable()
    {
        vfsStream::newFile('config/config.php', 0111)
            ->at($this->dir);
        $configFile = vfsStream::url('project/config/config.php');

        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('not readable');
        $this->generator->process($configFile);
    }

    public function testRaisesExceptionIfConfigFileDoesNotReturnArray()
    {
        vfsStream::newFile('config/config.php')
            ->at($this->dir)
            ->setContent('<' . '?php /* NO RETURN */');
        $configFile = vfsStream::url('project/config/config.php');

        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('did not return an array');
        $this->generator->process($configFile);
    }

    public function invalidMiddleware()
    {
        $pipelineConfigTemplate = <<< 'EOT'
<?php
return [
    'middleware_pipeline' => [
        [
            'middleware' => %s,
        ],
    ],
];
EOT;
        return [
            'int'    => [sprintf($pipelineConfigTemplate, '1')],
            'float'  => [sprintf($pipelineConfigTemplate, '1.1')],
            'object' => [sprintf($pipelineConfigTemplate, '(object) [\'foo\' => \'bar\']')],
        ];
    }

    /**
     * @dataProvider invalidMiddleware
     *
     * @param string $pipelineConfig
     */
    public function testRaisesExceptionIfMiddlewareInPipelineIsInvalid($pipelineConfig)
    {
        vfsStream::newFile('config/config.php')
            ->at($this->dir)
            ->setContent($pipelineConfig);
        $configFile = vfsStream::url('project/config/config.php');

        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('middleware specification');
        $this->generator->process($configFile);
    }

    public function testRaisesExceptionIfApplicationBootstrapIsMissingRunStatement()
    {
        vfsStream::newFile('config/config.php')
            ->at($this->dir)
            ->setContent(file_get_contents(__DIR__ . '/TestAsset/asset/config/config.php'));

        $publicIndex = file_get_contents(__DIR__ . '/TestAsset/asset/public/index.php');
        $publicIndex = str_replace('$app->run();', '', $publicIndex);

        vfsStream::newFile('public/index.php')
            ->at($this->dir)
            ->setContent($publicIndex);

        vfsStream::newDirectory('config/autoload', 0755)
            ->at($this->dir);
        $configFile = vfsStream::url('project/config/config.php');

        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('$app->run');
        $this->generator->process($configFile);
    }

    public function processArtifacts()
    {
        return [
            'pipeline' => ['config/pipeline.php'],
            'routes'   => ['config/routes.php'],
        ];
    }

    /**
     * @dataProvider processArtifacts
     *
     * @param string $filename
     */
    public function testProcessRaisesExceptionIfArtifactsFromPreviousRunArePresent($filename)
    {
        $this->generatePipeline();

        vfsStream::newFile($filename)
            ->at($this->dir)
            ->setContent('<' . "?php\nreturn [];");

        $configFile = vfsStream::url('project/config/config.php');

        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('previous run detected');
        $this->generator->process($configFile);
    }
}
