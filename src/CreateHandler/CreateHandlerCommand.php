<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Expressive\Tooling\TemplateResolutionTrait;

class CreateHandlerCommand extends Command
{
    use TemplateResolutionTrait;

    public const DEFAULT_SRC = '/src';

    public const HELP_HANDLER_DESCRIPTION = 'Create a PSR-15 request handler class file.';

    public const HELP_HANDLER = <<< 'EOT'
Creates a PSR-15 request handler class file named after the provided
class. For a path, it matches the class namespace against PSR-4 autoloader
namespaces in your composer.json.
EOT;

    public const HELP_HANDLER_ARG_HANDLER = <<< 'EOT'
Fully qualified class name of the request handler to create. This value
should be quoted to ensure namespace separators are not interpreted as
escape sequences by your shell.
EOT;

    public const HELP_HANDLER_OPT_NO_FACTORY = <<< 'EOT'
By default, this command generates a factory for the request handler it
creates, and registers it with the container. Passing this option disables
that feature.
EOT;

    public const HELP_HANDLER_OPT_NO_REGISTER = <<< 'EOT'
By default, when this command generates a factory for the request handler it
creates, it registers it with the container. Passing this option disables
registration of the generated factory with the container.
EOT;

    public const HELP_ACTION_DESCRIPTION = 'Create an action class file.';

    public const HELP_ACTION = <<< 'EOT'
Creates an action class file named after the provided class. For a path, it
matches the class namespace against PSR-4 autoloader namespaces in your
composer.json.
EOT;

    public const HELP_ACTION_ARG_ACTION = <<< 'EOT'
Fully qualified class name of the action class to create. This value
should be quoted to ensure namespace separators are not interpreted as
escape sequences by your shell.
EOT;

    public const HELP_ACTION_OPT_NO_FACTORY = <<< 'EOT'
By default, this command generates a factory for the action class it creates,
and registers it with the container. Passing this option disables that
feature.
EOT;

    public const HELP_ACTION_OPT_NO_REGISTER = <<< 'EOT'
By default, when this command generates a factory for the action class it
creates, it registers it with the container. Passing this option disables
registration of the generated factory with the container.
EOT;

    public const HELP_OPTION_WITHOUT_TEMPLATE = <<< 'EOT'
By default, this command generates a template for the newly generated class,
and adds functionality to it render the template. Passing this flag
disables template generation and invocation.
EOT;

    public const HELP_OPTION_WITH_TEMPLATE_EXTENSION = <<< 'EOT'
By default, this command will look for a template file extension name
first via the "templates.extension" configuration directive, and then
falling back to defaults based on the renderer type detected. If the
configuration directive is not found, and the command does not know
how to map the renderer discovered, it will raise an exception. You
may pass this option to specify a custom extension in that case.
EOT;

    public const HELP_OPTION_WITH_TEMPLATE_NAME = <<< 'EOT'
By default, this command uses a normalized version of the class name as the
template name. Use this option to provide an alternative template name
(minus the namespace) for the generated template. The template file will be
named using this name, using an extension base on the configured template
renderer.  If --without-template is provided, this option is ignored. 
EOT;

    public const HELP_OPTION_WITH_TEMPLATE_NAMESPACE = <<< 'EOT'
By default, this command uses a normalized version of the root namespace of the
class generated as the template namespace.  Use this option to provide an
alternate template namespace for the generated template. The template will be
placed in the path defined for that namespace if discovered; otherwise, it will
be placed in the path defined for the root namespace of the class created. If
--without-template is provided, this option is ignored.
EOT;

    public const STATUS_HANDLER_TEMPLATE = '<info>Creating request handler %s...</info>';

    public const STATUS_ACTION_TEMPLATE = '<info>Creating action %s...</info>';

    /**
     * Name of the argument that resolves to the new handler's name.
     *
     * @var string
     */
    private $handlerArgument = 'handler';

    /**
     * Root path of the project. Defaults to getcwd(). Mainly exists for
     * testing purposes, to allow injecting a virtual filesystem location.
     *
     * @var string
     */
    private $projectRoot;

    /**
     * Flag indicating whether or not to require the generated handler before
     * generating its factory. By default, this is true, as it is necessary
     * in order for the handler to be reflected. However, during testing, we do
     * not actually generate a handler, so we need a way to disable it.
     *
     * @var bool
     */
    private $requireHandlerBeforeGeneratingFactory = true;

    /**
     * Whether or not the template renderer is registered in the container.
     * @var bool
     */
    private $rendererIsRegistered = false;

    /**
     * Whether or not a template renderer is registered in configuration.
     */
    private $templateRendererIsRegistered = false;

    public function __construct(string $name = null, string $projectRoot = null, ContainerInterface $container = null)
    {
        $this->projectRoot = $projectRoot ?: realpath(getcwd());
        $this->container = $container;
        $this->rendererIsRegistered = $this->containerDefinesRendererService(
            $this->getContainer($this->projectRoot)
        );

        // Must do last, so that container and/or project root are in scope
        // when configure() is called.
        parent::__construct($name);
    }

    /**
     * Configure the console command.
     *
     * If the command is named `action:create`, this method sets the
     * $handlerArgument to "action", and then invokes the configureAction()
     * method before returning. Otherwise, it configures the command for
     * producing a handler.
     */
    protected function configure() : void
    {
        if (0 === strpos($this->getName(), 'action:')) {
            $this->configureAction();
        } else {
            $this->configureHandler();
        }

        if ($this->rendererIsRegistered) {
            $this->configureTemplateOptions();
        }
    }

    protected function configureAction() : void
    {
        $this->handlerArgument = 'action';
        $this->setDescription(self::HELP_ACTION_DESCRIPTION);
        $this->setHelp(self::HELP_ACTION);
        $this->addArgument('action', InputArgument::REQUIRED, self::HELP_ACTION_ARG_ACTION);
        $this->addOption('no-factory', null, InputOption::VALUE_NONE, self::HELP_ACTION_OPT_NO_FACTORY);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_ACTION_OPT_NO_REGISTER);
    }

    protected function configureHandler() : void
    {
        $this->setDescription(self::HELP_HANDLER_DESCRIPTION);
        $this->setHelp(self::HELP_HANDLER);
        $this->addArgument('handler', InputArgument::REQUIRED, self::HELP_HANDLER_ARG_HANDLER);
        $this->addOption('no-factory', null, InputOption::VALUE_NONE, self::HELP_HANDLER_OPT_NO_FACTORY);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_HANDLER_OPT_NO_REGISTER);
    }

    protected function configureTemplateOptions() : void
    {
        $this->addOption(
            'with-template-namespace',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPTION_WITH_TEMPLATE_NAMESPACE
        );
        $this->addOption(
            'with-template-name',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPTION_WITH_TEMPLATE_NAME
        );
        $this->addOption(
            'with-template-extension',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPTION_WITH_TEMPLATE_EXTENSION
        );
        $this->addOption(
            'without-template',
            null,
            InputOption::VALUE_NONE,
            self::HELP_OPTION_WITHOUT_TEMPLATE
        );
    }

    /**
     * Execute console command.
     *
     * @return int Exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $handler = $input->getArgument($this->handlerArgument);

        $template = $this->handlerArgument === 'action'
            ? self::STATUS_ACTION_TEMPLATE
            : self::STATUS_HANDLER_TEMPLATE;
        $output->writeln(sprintf($template, $handler));

        $skeleton = ClassSkeletons::CLASS_SKELETON;
        $substitutions = [];
        $templateNamespace = null;
        $templateName = null;
        $templateExtension = null;

        if ($this->rendererIsRegistered && ! $input->getOption('without-template')) {
            $skeleton = ClassSkeletons::CLASS_SKELETON_WITH_TEMPLATE;
            $templateNamespace = $input->getOption('with-template-namespace')
                ?: $this->getTemplateNamespaceFromClass($handler);
            $templateName = $input->getOption('with-template-name')
                ?: $this->getTemplateNameFromClass($handler);
            $templateExtension = $input->getOption('with-template-extension');
            $substitutions['%template-namespace%'] = $templateNamespace;
            $substitutions['%template-name%'] = $templateName;
        }

        $generator = new CreateHandler($skeleton, $this->projectRoot);
        $path = $generator->process($handler, $substitutions);

        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf(
            '<info>- Created class %s, in file %s</info>',
            $handler,
            $path
        ));

        if ($this->rendererIsRegistered
            && ! $input->getOption('without-template')
        ) {
            $this->generateTemplate(
                $handler,
                $templateNamespace,
                $templateName,
                $templateExtension,
                $path,
                $output
            );
        }

        if (! $input->getOption('no-factory')) {
            return $this->generateFactory($handler, $path, $input, $output);
        }

        return 0;
    }

    private function generateTemplate(
        string $handlerClass,
        string $templateNamespace,
        string $templateName,
        ?string $templateExtension,
        string $path,
        OutputInterface $output
    ) : void {
        if ($this->requireHandlerBeforeGeneratingFactory) {
            require_once $path;
        }

        $generator = new CreateTemplate($this->projectRoot);
        $template = $generator->generateTemplate(
            $handlerClass,
            $templateNamespace,
            $templateName,
            $templateExtension
        );

        $output->writeln(sprintf(
            '<info>- Created template %s in file %s</info>',
            $template->getName(),
            $template->getPath()
        ));
    }

    private function generateFactory(
        string $handlerClass,
        string $path,
        InputInterface $input,
        OutputInterface $output
    ) : int {
        if ($this->requireHandlerBeforeGeneratingFactory) {
            require_once $path;
        }

        $factoryInput = new ArrayInput([
            'command'       => 'factory:create',
            'class'         => $handlerClass,
            '--no-register' => $input->getOption('no-register'),
        ]);
        $command = $this->getApplication()->find('factory:create');
        return $command->run($factoryInput, $output);
    }
}
