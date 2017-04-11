<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\MigrateOriginalMessageCalls;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateOriginalMessageCallsCommand extends Command
{
    const DEFAULT_SRC = '/src';

    const HELP = <<< 'EOT'
Migrate an Expressive application to remove calls to legacy
request/response methods.

Scans all PHP files under the --src directory for any calls to
getOriginalRequest(), getOriginalUri(), or getOriginalResponse().

In the case of getOriginalResponse(), the call will be rewritten to
getAttribute('originalRequest', {requestVariable}).

In the case of getOriginalUri(), the call will be rewritten to
getAttribute('originalUri', {requestVariable}->getUri()).

If any getOriginalResponse() calls are detected, the script will present
a warning indicating the file(s) and detail how to correct these
manually.
EOT;

    const HELP_OPT_SRC = <<< 'EOT'
Specify a path to PHP files to scan for
getOriginalRequest/getOriginalUri/getOriginalResponse calls. If not
specified, assumes src/ under the current working path.
EOT;

    const TEMPLATE_RESPONSE_DETAILS = <<< 'EOT'
To correct these files, look for a request argument, and update the
following calls ($response may be a different variable):

    $response->getOriginalResponse()

to:

    $request->getAttribute('originalResponse', $response)

($request may be a different variable)
EOT;

    /**
     * @var null|string Path from which to resolve default src directory
     */
    private $projectDir;

    /**
     * @var null|string Project root against which to scan.
     */
    public function setProjectDir($path)
    {
        $this->projectDir = $path;
    }

    /**
     * Configure the console command.
     */
    protected function configure()
    {
        $this->setDescription('Migrate getOriginal*() calls to request attributes.');
        $this->setHelp(self::HELP);
        $this->addOption('src', 's', InputOption::VALUE_REQUIRED, self::HELP_OPT_SRC);
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
        $src = $this->getSrcDir($input);

        $output->writeln('<info>Scanning for usage of Stratigility HTTP message decorators...</info>');

        $converter = new ConvertOriginalMessageCalls($output);
        $converter->process($src);

        if ($converter->originalResponseFound()) {
            $output->writeln('<error>One or more files contained calls to getOriginalResponse().</error>');
            $output->writeln('<info>Check the above logs to determine which files need attention.</info>');
            $output->writeln(self::TEMPLATE_RESPONSE_DETAILS);
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * Retrieve the project root directory.
     *
     * Uses result of getcwd() if not previously set.
     *
     * @return string
     */
    private function getProjectDir()
    {
        return $this->projectDir ?: getcwd();
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws ArgvException
     */
    private function getSrcDir(InputInterface $input)
    {
        $path = $input->getOption('src') ?: self::DEFAULT_SRC;
        $path = $this->getProjectDir() . '/' . $path;

        if (! is_dir($path)) {
            throw new ArgvException(sprintf(
                'Invalid --src argument "%s"; directory does not exist',
                $path
            ));
        }

        return $path;
    }
}
