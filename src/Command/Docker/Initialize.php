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
use eZ\Launchpad\Core\ProjectWizard;
use eZ\Launchpad\Core\TaskExecutor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Initialize extends Command
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
        $this->setName('docker:initialize')->setDescription('Initialize the project and all the services.');
        $this->setAliases(['docker:init', 'initialize', 'init']);
        $this->addArgument('repository', InputArgument::OPTIONAL, 'eZ Platform Repository', 'ezsystems/ezplatform');
        $this->addArgument('version', InputArgument::OPTIONAL, 'eZ Platform Version', '2.*');
        $this->addArgument(
            'initialdata',
            InputArgument::OPTIONAL,
            'Installer: If avaiable uses "composer run-script <initialdata>", if not uses ezplatform:install command',
            'ezplatform-install'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $fs = new Filesystem();
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        // Get the Payload docker-compose.yml
        $compose = new DockerCompose("{$this->getPayloadDir()}/dev/docker-compose.yml");
        $wizard = new ProjectWizard($this->io, $this->projectConfiguration);

        // Ask the questions
        list($networkName, $networkPort, $httpBasics, $selectedServices, $provisioningName, $composeFileName) = $wizard(
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

        // if we are on eZ 1.x then we don't want PHP 7.3
        if (1 === (int) str_replace(['^', '~'], '', $input->getArgument('version'))) {
            $enginDockerFilePath = "{$provisioningFolder}/dev/engine/Dockerfile";
            $engineDockerFileContent = file_get_contents($enginDockerFilePath);
            file_put_contents(
                $enginDockerFilePath,
                str_replace(
                    'docker-php-ez-engine:7.3',
                    'docker-php-ez-engine:7.2',
                    $engineDockerFileContent
                )
            );
            // v2 and v1 share the same vhost
            rename("{$provisioningFolder}/dev/nginx/nginx_v2.conf", "{$provisioningFolder}/dev/nginx/nginx.conf");
        }

        // in version 2.x we have another DOCUMENT_ROOT, and v3 can provide more
        if (2 === (int) str_replace(['^', '~'], '', $input->getArgument('version'))) {
            rename("{$provisioningFolder}/dev/nginx/nginx_v2.conf", "{$provisioningFolder}/dev/nginx/nginx.conf");
        }

        // no need for v2 nginx on v3
        if (3 === (int) str_replace(['^', '~'], '', $input->getArgument('version'))) {
            unlink("{$provisioningFolder}/dev/nginx/nginx_v2.conf");
        }

        // Clean the Compose File
        $compose->removeUselessEnvironmentsVariables();

        // Get the Payload README.md
        $fs->copy("{$this->getPayloadDir()}/README.md", "{$provisioningFolder}/README.md");

        // create the local configurations
        $localConfigurations = [
            'provisioning.folder_name' => $provisioningName,
            'docker.compose_filename' => $composeFileName,
            'docker.network_name' => $networkName,
            'docker.network_prefix_port' => $networkPort,
        ];

        foreach ($httpBasics as $name => $httpBasic) {
            list($host, $user, $pass) = $httpBasic;
            $localConfigurations["composer.http_basic.{$name}.host"] = $host;
            $localConfigurations["composer.http_basic.{$name}.login"] = $user;
            $localConfigurations["composer.http_basic.{$name}.password"] = $pass;
        }

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
            $input
        );

        // remove unused solr
        if (!$compose->hasService('solr')) {
            $fs->remove("{$provisioningFolder}/dev/solr");
        }
        // remove unused varnish
        if (!$compose->hasService('varnish')) {
            $fs->remove("{$provisioningFolder}/dev/varnish");
        }

        $this->projectConfiguration->setEnvironment('dev');
        $this->projectStatusDumper->dump('ncsi');
    }

    protected function innerInitialize(
        Docker $dockerClient,
        DockerCompose $compose,
        string $composeFilePath,
        InputInterface $input
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

        // Fix #7
        // if eZ EE is selected then the DB is not selected by the install process
        // we have to do it manually here
        $repository = $input->getArgument('repository');
        $initialdata = $input->getArgument('initialdata');

        $version = $input->getArgument('version');
        // Change default when on eZ Platform v1 to "clean" / "ezplatform-ee-clean"
        if ('ezplatform-install' === $initialdata && 1 === (int) str_replace(['^', '~'], '', $version)) {
            $initialdata = (false !== strpos($repository, 'ezplatform-ee') ? 'ezplatform-ee-clean' : 'clean');
        }

        $executor->eZInstall($version, $repository, $initialdata);
        if ($compose->hasService('solr')) {
            $executor->eZInstallSolr();
        }
        $compose->dump($composeFilePath);

        $dockerClient->up(['-d']);
        $executor->composerInstall();

        if ($compose->hasService('solr')) {
            $executor->createCore();
            $executor->indexSolr();
        }
    }
}
