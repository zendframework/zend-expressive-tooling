<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\Module;

use Composer\Config;
use Composer\IO\IOInterface;
use Zend\Stdlib\ConsoleHelper;

class ComposerConsole implements IOInterface
{
    /**
     * @var ConsoleHelper
     */
    private $consoleHelper;

    /**
     * @param ConsoleHelper $consoleHelper
     */
    public function __construct(ConsoleHelper $consoleHelper)
    {
        $this->consoleHelper = $consoleHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = true, $verbosity = self::NORMAL)
    {
        if ($newline) {
            $this->consoleHelper->writeLine($messages);
        } else {
            $this->consoleHelper->write($messages);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeError($messages, $newline = true, $verbosity = self::NORMAL)
    {
        if ($newline) {
            $this->consoleHelper->writeLine($messages, true, STDERR);
        } else {
            $this->consoleHelper->write($messages, true, STDERR);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    public function overwrite($messages, $newline = true, $size = null, $verbosity = self::NORMAL)
    {
        throw new Exception\RuntimeException('Method "overwrite" is not supported.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    public function overwriteError($messages, $newline = true, $size = null, $verbosity = self::NORMAL)
    {
        throw new Exception\RuntimeException('Method "overwriteError" is noot supported.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    public function ask($question, $default = null)
    {
        throw new Exception\RuntimeException('Method "ask" is not supported');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    public function askConfirmation($question, $default = true)
    {
        throw new Exception\RuntimeException('Method "askConfirmation" is not supported');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    public function askAndValidate($question, $validator, $attempts = null, $default = null)
    {
        throw new Exception\RuntimeException('Method "askAndValidate" is not supported');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    public function askAndHideAnswer($question)
    {
        throw new Exception\RuntimeException('Method "askAndHideAnswer" is not supported');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    public function select(
        $question,
        $choices,
        $default,
        $attempts = false,
        $errorMessage = 'Value "%s" is invalid',
        $multiselect = false
    ) {
        throw new Exception\RuntimeException('Method "select" is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthentications()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasAuthentication($repositoryName)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthentication($repositoryName)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthentication($repositoryName, $username, $password = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function loadConfiguration(Config $config)
    {
    }
}
