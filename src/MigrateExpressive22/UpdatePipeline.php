<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\MigrateExpressive22;

use Symfony\Component\Console\Output\OutputInterface;

class UpdatePipeline
{
    // @codingStandardsIgnoreStart
    /**
     * PCRE strings to match, and their replacements.
     * @var string[]
     */
    private $matches = [
        '/-\>pipeRoutingMiddleware\(\)/'                        => '->pipe(\Zend\Expressive\Router\Middleware\RouteMiddleware::class)',
        '/-\>pipeDispatchMiddleware\(\)/'                       => '->pipe(\Zend\Expressive\Router\Middleware\DispatchMiddleware::class)',
        '/-\>pipe\(.*?(Implicit(Head|Options)Middleware).*?\)/' => '->pipe(\Zend\Expressive\Router\Middleware\\\\$1::class)'
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @param string $projectPath
     * @return void
     */
    public function __invoke(OutputInterface $output, $projectPath)
    {
        $filename = sprintf('%s/config/pipeline.php', $projectPath);
        $contents = '';
        $fh = fopen($filename, 'r+');

        while (! feof($fh)) {
            if (false === ($line = fgets($fh))) {
                break;
            }

            $contents .= $this->matchAndReplace($output, $line);
        }

        fclose($fh);

        file_put_contents($filename, $contents);
    }

    /**
     * @param string $line
     * @return string
     */
    private function matchAndReplace(OutputInterface $output, $line)
    {
        $updated = $line;
        foreach ($this->matches as $pattern => $replacement) {
            $updated = preg_replace($pattern, $replacement, $updated);
        }

        if ($updated !== $line) {
            $output->writeln(sprintf(
                '<info>Rewrote line "%s" to "%s"</info>',
                $line,
                $updated
            ));
        }

        return $updated;
    }
}
