<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\ScanForErrorMiddleware;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\ErrorConsoleTrait;

class ScanForErrorMiddlewareCommand extends Command
{
    use ErrorConsoleTrait;

    const DEFAULT_SRC = '/src';

    const HELP = <<< 'EOT'
Scans the directory provided by --dir (defaulting to src/) for classes
that either implement Zend\Stratigility\ErrorMiddlewareInterface,
or which implement __invoke() using that signature. Any that are
discovered are reported to the console.
EOT;

    const HELP_OPT_DIR = 'Specify a path to scan; defaults to src/.';

    const TEMPLATE_RESPONSE_DETAILS = <<< 'EOT'
The files listed should be converted to normal middleware. These should
include a try/catch block around a call to $next(), catching
domain-specific exceptions and re-throwing all others:

    try {
    } catch (SomeSpecificException $e) {
        // handle this exception type
    } catch (\Throwable $e) {
        // PHP 7
        throw ($e);
    } catch (\Exception $e) {
        // PHP 5 fallback
        throw ($e);
    }

Any middleware that calls $next() with the third "$err" argument should
be updated to raise an exception instead.
EOT;

    public $projectDir = '.';

    /**
     * Configure the console command.
     */
    protected function configure()
    {
        $this->setDescription('Scan for legacy error middleware or error middleware invocation.');
        $this->setHelp(self::HELP);
        $this->addOption('dir', 'd', InputOption::VALUE_REQUIRED, self::HELP_OPT_DIR);
    }

    /**
     * Execute console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $src = $this->getSrcDir($input);
        } catch (ArgvException $e) {
            $console = $this->getErrorConsole($output);
            $console->writeln(sprintf(
                '<error>Unable to determine source directory: %s</error>',
                $e->getMessage()
            ));
            return 1;
        }

        $output->writeln('<info>Scanning for error middleware or error middleware invocation...</info>');
        $output->writeln('');

        $scanner = new Scanner($src, $output);
        $scanner->scan();

        $count = $scanner->count();
        if ($count > 0) {
            $output->writeln('');
            $output->writeln(sprintf(
                '<error>%d file%s contained error middleware or called error middleware.</error>',
                $count,
                $count > 1 ? 's' : ''
            ));
            $output->writeln('');
            $output->writeln('<info>Check the above logs to determine which files need attention.</info>');
            $output->writeln('');
            $output->writeln(self::TEMPLATE_RESPONSE_DETAILS);
            $output->writeln('');
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws ArgvException
     */
    private function getSrcDir(InputInterface $input)
    {
        $path = $input->getOption('dir') ?: self::DEFAULT_SRC;
        $path = $this->projectDir . DIRECTORY_SEPARATOR . $path;

        if (! is_dir($path)
            || ! is_readable($path)
        ) {
            throw new ArgvException(sprintf(
                'Invalid --dir argument "%s"; directory does not exist or is not readable',
                $path
            ));
        }

        return $path;
    }
}
