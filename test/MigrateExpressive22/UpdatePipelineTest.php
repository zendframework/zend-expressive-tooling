<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Tooling\MigrateExpressive22;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\MigrateExpressive22\UpdatePipeline;

class UpdatePipelineTest extends TestCase
{
    public function setUp()
    {
        $this->root = vfsStream::setup('expressive22');
        $this->url = vfsStream::url('expressive22');
        mkdir($this->url . '/config');
        touch($this->url . '/config/pipeline.php');
    }

    public function testUpdatesPipelineReferencingFullyQualifiedNames()
    {
        $originalContents = <<< 'EOT'
$app->pipeRoutingMiddleware();
$app->pipe(\Zend\Expressive\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Zend\Expressive\Middleware\ImplicitOptionsMiddleware::class);
$app->pipeDispatchMiddleware();
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Zend\Expressive\Router\Middleware\RouteMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\DispatchMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('pipeRoutingMiddleware()'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('pipeDispatchMiddleware()'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }

    public function testUpdatesPipelineReferencingRelativeNames()
    {
        $originalContents = <<< 'EOT'
$app->pipe(Zend\Expressive\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(Zend\Expressive\Middleware\ImplicitOptionsMiddleware::class);
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }

    public function testUpdatesPipelineReferencingClassNamesOnly()
    {
        $originalContents = <<< 'EOT'
$app->pipe(ImplicitHeadMiddleware::class);
$app->pipe(ImplicitOptionsMiddleware::class);
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }

    public function testUpdatesPipelineReferencingOnlyRoutingAndDispatchMiddleware()
    {
        $originalContents = <<< 'EOT'
$app->pipeRoutingMiddleware();
$app->pipeDispatchMiddleware();
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Zend\Expressive\Router\Middleware\RouteMiddleware::class);
$app->pipe(\Zend\Expressive\Router\Middleware\DispatchMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('pipeRoutingMiddleware()'))->shouldBeCalled();
        $output->writeln(Argument::containingString('pipeDispatchMiddleware()'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }
}
