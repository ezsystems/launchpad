<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Console\Application;
use eZ\Launchpad\Core\Client\Docker;
use eZ\Launchpad\Core\Command;
use eZ\Launchpad\Core\DockerCompose;
use eZ\Launchpad\Core\ProcessRunner;
use eZ\Launchpad\Core\ProjectStatusDumper;
use eZ\Launchpad\Core\ProjectWizardIbexa;
use eZ\Launchpad\Core\TaskExecutor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class InitializeIbexa extends Command
{
    /**
     * @var ProjectStatusDumper
     */
    protected $projectStatusDumper;

    public function __construct(ProjectStatusDumper $projectStatusDumper)
    {
        parent::__construct();
        $this->projectStatusDumper = $projectStatusDumper;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setIo($this->io);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:initialize_ibexa')->setDescription('Initialize IBEXA project and all the services.');
        $this->setAliases(['docker:init_ibexa', 'initialize_ibexa', 'init_ibexa']);
        $this->addArgument('version', InputArgument::OPTIONAL, 'Ibexa Version', '3.3.*');
        $this->addArgument(
            'initialdata',
            InputArgument::OPTIONAL,
            'Installer: If avaiable uses "composer run-script <initialdata>", if not uses ibexa:install command',
            'ibexa:install'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        // Get the Payload docker-compose.yml
        $compose = new DockerCompose("{$this->getPayloadDir()}/dev/docker-compose.yml");

        $wizard = new ProjectWizardIbexa($this->io, $this->projectConfiguration);

        // Ask the questions
        [$networkName, $networkPort, $selectedServices, $provisioningName, $composeFileName] = $wizard(
            $compose
        );
        $compose->filterServices($selectedServices);
        // start the scafolding of the Payload
        $provisioningFolder = "{$this->projectPath}/{$provisioningName}";
        $fs->mkdir("{$provisioningFolder}/dev");
        $fs->mirror("{$this->getPayloadDir()}/dev", "{$provisioningFolder}/dev");
        $fs->chmod(
            [
                "{$provisioningFolder}/dev/nginx/entrypoint.bash",
                "{$provisioningFolder}/dev/engine/entrypoint.bash",
                "{$provisioningFolder}/dev/solr/entrypoint.bash",
            ],
            0755
        );
        unset($selectedServices);

        //Update composer to V2
        $enginEntryPointPath = "{$provisioningFolder}/dev/engine/entrypoint.bash";
        $engineEntryPointContent = file_get_contents($enginEntryPointPath);
        file_put_contents(
            $enginEntryPointPath,
            str_replace(
                'self-update --1',
                'self-update --2',
                $engineEntryPointContent
            )
        );
        // Get the Payload README.md
        $fs->copy("{$this->getPayloadDir()}/README.md", "{$provisioningFolder}/README.md");
        // create the local configurations
        $localConfigurations = [
            'provisioning.folder_name' => $provisioningName,
            'docker.compose_filename' => $composeFileName,
            'docker.network_name' => $networkName,
            'docker.network_prefix_port' => $networkPort,
        ];

        $this->projectConfiguration->setMultiLocal($localConfigurations);

        // Create the docker Client
        $options = [
            'compose-file' => "{$provisioningFolder}/dev/{$composeFileName}",
            'network-name' => $networkName,
            'network-prefix-port' => $networkPort,
            'project-path' => $this->projectPath,
            'provisioning-folder-name' => $provisioningName,
            'host-machine-mapping' => $this->projectConfiguration->get('docker.host_machine_mapping'),
            'composer-cache-dir' => $this->projectConfiguration->get('docker.host_composer_cache_dir'),
        ];
        $dockerClient = new Docker($options, new ProcessRunner(), $this->optimizer);
        $this->projectStatusDumper->setDockerClient($dockerClient);

        // do the real work
        $this->innerInitialize(
            $dockerClient,
            $compose,
            "{$provisioningFolder}/dev/{$composeFileName}",
            $input,
            $wizard->getPackage()
        );
        $this->projectConfiguration->setEnvironment('dev');
        $this->projectStatusDumper->dump('ncsi');

        return Command::SUCCESS;
    }

    protected function innerInitialize(
        Docker $dockerClient,
        DockerCompose $compose,
        string $composeFilePath,
        InputInterface $input,
        string $package
    ): void {
        $tempCompose = clone $compose;
        $tempCompose->cleanForInitialize();
        // dump the temporary DockerCompose.yml without the mount and env vars in the provisioning folder
        $tempCompose->dump($composeFilePath);
        unset($tempCompose);
        // Do the first pass to get eZ Platform and related files
        $dockerClient->build(['--no-cache']);
        $dockerClient->up(['-d']);
        $executor = new TaskExecutor($dockerClient, $this->projectConfiguration, $this->requiredRecipes);
        $executor->composerInstall();
        $initialdata = $input->getArgument('initialdata');
        $normalizedVersion = trim($input->getArgument('version'), 'v');
        $executor->iBexaInstall($normalizedVersion, $package);
        if ($compose->hasService('solr')) {
            $executor->eZInstallSolr();
        }
        $compose->dump($composeFilePath);

        $dockerClient->up(['-d']);
        $executor->composerInstall();

        if ($compose->hasService('solr')) {
            $executor->createCore();
        }

        $executor->iBexaDatabaseInitData($initialdata);
    }
}
