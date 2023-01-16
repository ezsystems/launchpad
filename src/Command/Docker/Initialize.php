<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Configuration\CmsVersionRegistry;
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

    /**
     * @var CmsVersionRegistry
     */
    protected $cmsVersionRegistry;

    public function __construct(ProjectStatusDumper $projectStatusDumper, CmsVersionRegistry $cmsVersionRegistry)
    {
        parent::__construct();
        $this->projectStatusDumper = $projectStatusDumper;
        $this->cmsVersionRegistry = $cmsVersionRegistry;
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
        $this->addArgument('version', InputArgument::OPTIONAL, 'eZ Platform Version', '3.*');
        $this->addArgument(
            'initialdata',
            InputArgument::OPTIONAL,
            'Installer: If avaiable uses "composer run-script <initialdata>", if not uses ezplatform:install command',
            'ezplatform-install'
        );
    }

    private function isIbexa(InputInterface $input): bool
    {
        return false !== strpos($input->getArgument('repository'), 'ibexa');
    }

    private function getEzPlatformMajorVersion(InputInterface $input): string
    {
        $normalizedVersion = trim($input->getArgument('version'), 'v');
        $normalizedProvider = explode(
            '/',
            $input->hasArgument('repository') ?
            $input->getArgument('repository') : ''
        )[0];
        $normalizedMajorVersion = (int) str_replace(['^', '~'], '', $normalizedVersion);
        $isNetgenMedia = 'netgen' === $normalizedProvider;

        if ($isNetgenMedia) {
            ++$normalizedMajorVersion;
        }

        return sprintf('%s-%d', $normalizedProvider, $normalizedMajorVersion);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        // Get the Payload docker-compose.yml
        $compose = new DockerCompose("{$this->getPayloadDir()}/dev/docker-compose.yml");
        $wizard = new ProjectWizard($this->io, $this->projectConfiguration);

        // Ask the questions
        [$networkName, $networkPort, $httpBasics, $selectedServices, $provisioningName, $composeFileName] = $wizard(
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

        $eZMajorVersion = $this->getEzPlatformMajorVersion($input);
        $this->projectConfiguration->setLocal('project.cms_version', $eZMajorVersion);

        $versionConfig = $this->cmsVersionRegistry->getVersion($eZMajorVersion);

        // Set php version according to CMS version
        $enginDockerFilePath = "{$provisioningFolder}/dev/engine/Dockerfile";
        $engineDockerFileContent = file_get_contents($enginDockerFilePath);
        file_put_contents(
            $enginDockerFilePath,
            str_replace(
                '$PHP_VERSION',
                (string) $versionConfig->phpVersion,
                $engineDockerFileContent
            )
        );

        // eZ Platform < 3 has another vhost
        $vhostConfFiles = [
            'ezsystems-2' => "{$provisioningFolder}/dev/nginx/nginx_v2.conf",
            'ezsystems-3' => "{$provisioningFolder}/dev/nginx/nginx_v2.conf",
            'ibexa-3' => "{$provisioningFolder}/dev/nginx/nginx_v3.conf",
            'ibexa-4' => "{$provisioningFolder}/dev/nginx/nginx_v4.conf",
        ];
        if (isset($vhostConfFiles[$eZMajorVersion])) {
            rename($vhostConfFiles[$eZMajorVersion], "{$provisioningFolder}/dev/nginx/nginx.conf");
        }
        foreach ($vhostConfFiles as $vhostCmsVersion => $vhostConfFile) {
            if ($vhostCmsVersion !== $eZMajorVersion && file_exists($vhostConfFile)) {
                unlink($vhostConfFile);
            }
        }

        // eZ Platform < 3 only support solr 6. Replace unsupported solr 7.7 by 6.6.2
        if ($compose->hasService('solr')) {
            $composeFilePath = "{$provisioningFolder}/dev/{$composeFileName}";
            $compose->dump($composeFilePath);
            $composeFileContent = file_get_contents($composeFilePath);
            file_put_contents(
                $composeFilePath,
                str_replace(
                    '$SOLR_VERSION',
                    (string) $versionConfig->solrVersion,
                    $composeFileContent
                )
            );
            $compose = new DockerCompose($composeFilePath);
        }

        // Ibexa >= 3.3.x , update composer to v2
        $engineEntryPointPath = "{$provisioningFolder}/dev/engine/entrypoint.bash";
        $engineEntryPointContent = file_get_contents($engineEntryPointPath);
        file_put_contents(
            $engineEntryPointPath,
            str_replace(
                '$COMPOSER_VERSION',
                (string) $versionConfig->composerVersion,
                $engineEntryPointContent
            )
        );

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
            [$host, $user, $pass] = $httpBasic;
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
            'project-cms-path-container' => $versionConfig->cmsRoot,
            'project-session-handler' => $versionConfig->sessionHandler,
            'project-session-handler' => $versionConfig->sessionHandler,
            'console-path' => $versionConfig->consolePath,
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

        return Command::SUCCESS;
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

        $normalizedVersion = trim($input->getArgument('version'), 'v');
        // Change default when on eZ Platform v1 to "clean" / "ezplatform-ee-clean"
        if ('ezplatform-install' === $initialdata && 1 === (int) str_replace(['^', '~'], '', $normalizedVersion)) {
            $initialdata = (false !== strpos($repository, 'ezplatform-ee') ? 'ezplatform-ee-clean' : 'clean');
        }

        if (!$this->isIbexa($input)) {
            $executor->eZInstall($normalizedVersion, $repository, $initialdata);
        } else {
            // for sure it is wrong with ibexa/* so we change it by defaultc
            if ('ezplatform-install' === $initialdata) {
                $initialdata = str_replace('/', '-', $repository);
            }
            $executor->ibexaInstall($normalizedVersion, $repository, $initialdata);
        }

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
