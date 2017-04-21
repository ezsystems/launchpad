<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Console;

use eZ\Launchpad\Configuration\Configuration;
use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Core;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Application.
 */
class Application extends BaseApplication
{
    /**
     * @var string
     */
    private $env;

    /**
     * @var string
     */
    private static $logo = '<fg=cyan>
               <fg=yellow>*</>
       <fg=yellow>*</>             <fg=yellow>*</>
                    <fg=red>___</>
              |     <fg=red>| |</>
    <fg=yellow>*</>        / \    <fg=red>| |</>
            |<fg=magenta>--o</>|<fg=blue>===</><fg=red>|</><fg=black>-</><fg=red>|</>
            |<fg=magenta>---</>|   <fg=red>| |</>
       <fg=yellow>*</>   /     \  <fg=red>| |</>
          |  <fg=white;options=bold>eZ</>   | <fg=red>| |</>
          |       |<fg=blue>=</><fg=red>| |</>
          <fg=white;options=bold>Launchpad</> <fg=red>| |</>
          |_______| <fg=red>|<fg=black>_</>|</>
           |<fg=red;bg=yellow>@</>| |<fg=red;bg=yellow>@</>|  <fg=red>| |</>
  <fg=green>____\|/___________</><fg=red>|</><fg=green>_</><fg=red>|</><fg=green>_</>

    </>';

    /**
     * The Container.
     *
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * Set The Container.
     *
     * @param ContainerBuilder $container The Container
     *
     * @return Application
     */
    public function setContainer(ContainerBuilder $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the Env.
     *
     * @param $env
     *
     * @return Application
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogo()
    {
        return self::$logo.$this->getLongVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return self::$logo.parent::getHelp();
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        // configure styles here
        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfiguration($input);

        // add the command from the configuration
        $commands = $this->container->findTaggedServiceIds('ezlaunchpad.command');
        foreach ($commands as $def => $values) {
            $command = $this->container->get($def);
            if ($command instanceof Core\Command) {
                $this->add($command);
            }
        }

        return parent::doRun($input, $output);
    }

    /**
     * Load the configuration file.
     *
     * @param InputInterface $input
     */
    protected function loadConfiguration(InputInterface $input)
    {
        $appDir      = __DIR__.'/../../';
        $projectPath = getcwd();
        $this->container->setParameter('app_dir', $appDir);
        $this->container->setParameter('app_env', $this->env);
        $this->container->setParameter('project_path', $projectPath);

        // Override the defaults values
        $globalFilePath = $input->getParameterOption(['--config', '-c'], EZ_HOME.'/ez.yml');
        $fs             = new Filesystem();
        $configs        = [];

        if ($fs->exists($globalFilePath)) {
            $configs[] = Yaml::parse(file_get_contents($globalFilePath));
        } else {
            if ($input->hasParameterOption(['--config', '-c'])) {
                throw new FileNotFoundException("Configuraton file {$globalFilePath} not found.");
            }
        }

        // Load the local values and OVERRIDE
        $localFilePath = $projectPath.'/.ezlaunchpad.yml';
        if ($fs->exists($localFilePath)) {
            $configs[] = Yaml::parse(file_get_contents($localFilePath));
        }
        $processor              = new Processor();
        $configuration          = new Configuration();
        $processedConfiguration = $processor->processConfiguration(
            $configuration,
            $configs
        );

        // set the dispatcher
        $this->container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));

        // set the project configuration service
        $this->container->setDefinition(
            'project_configuration',
            new Definition(
                ProjectConfiguration::class,
                [
                    $globalFilePath,
                    $localFilePath,
                    $processedConfiguration,
                ]
            )
        );
        // Compile
        $this->compileConfiguration();
    }

    /**
     * Find and compile the configuration.
     */
    protected function compileConfiguration()
    {
        $this->container->compile();
        $eventDispatcher = $this->container->get('event_dispatcher');
        /* @var EventDispatcher $eventDispatcher */
        $this->setDispatcher($eventDispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(
            new InputOption(
                '--config',
                '-c',
                InputOption::VALUE_REQUIRED,
                'use the given file as configuration file, instead of the default one ('.EZ_HOME.'/ez.yml'.').'
            )
        );

        return $definition;
    }
}
