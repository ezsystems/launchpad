<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Console;

use eZ\Launchpad\Configuration\Configuration;
use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Core;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

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
     * @var ContainerBuilder
     */
    protected $container;

    public function setContainer(ContainerBuilder $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function setEnv(string $env): self
    {
        $this->env = $env;

        return $this;
    }

    public function getLogo(): string
    {
        return self::$logo.$this->getLongVersion();
    }

    public function getHelp(): string
    {
        return self::$logo.parent::getHelp();
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        // configure styles here
        return parent::run($input, $output);
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
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

    protected function loadConfiguration(InputInterface $input): void
    {
        $appDir = __DIR__.'/../../';
        $projectPath = getcwd();
        $this->container->setParameter('app_dir', $appDir);
        $this->container->setParameter('app_env', $this->env);
        $this->container->setParameter('project_path', $projectPath);

        // Override the defaults values
        $globalFilePath = $input->getParameterOption(['--config', '-c'], EZ_HOME.'/ez.yml');
        $fs = new Filesystem();
        $configs = [];

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
        $processor = new Processor();
        $configuration = new Configuration();
        $processedConfiguration = $processor->processConfiguration(
            $configuration,
            $configs
        );

        // set the dispatcher
        $this->container->setDefinition(
            'event_dispatcher',
            (new Definition(EventDispatcher::class))->setPublic(true)
        );

        // set the project configuration service
        $this->container->setDefinition(
            ProjectConfiguration::class,
            (new Definition(
                ProjectConfiguration::class,
                [
                    $globalFilePath,
                    $localFilePath,
                    $processedConfiguration,
                ]
            ))->setPublic(true)
        );
        // Compile
        $this->compileConfiguration();
    }

    /**
     * Find and compile the configuration.
     */
    protected function compileConfiguration(): void
    {
        $this->container->compile();
        $eventDispatcher = $this->container->get('event_dispatcher');
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $this->setDispatcher($eventDispatcher);
        }
    }

    protected function getDefaultInputDefinition(): InputDefinition
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
