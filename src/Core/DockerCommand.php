<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Configuration\CmsVersionRegistry;
use eZ\Launchpad\Core\Client\Docker;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class DockerCommand extends Command
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Docker
     */
    protected $dockerClient;

    /**
     * @var TaskExecutor
     */
    protected $taskExecutor;

    /**
     * @var CmsVersionRegistry
     */
    protected $cmsVersionRegistry;

    public function __construct(CmsVersionRegistry $cmsVersionRegistry)
    {
        $this->cmsVersionRegistry = $cmsVersionRegistry;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev');
        $this->addOption(
            'docker-env',
            'd',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Docker environment variables',
            []
        );
    }

    public function getDockerClient(): Docker
    {
        return $this->dockerClient;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->environment = $input->getOption('env');
        $this->projectConfiguration->setEnvironment($this->environment);
        $versionConfig = $this->cmsVersionRegistry->getVersion($this->projectConfiguration->get('project.cms_version'));

        $fs = new Filesystem();
        $currentPwd = $this->projectPath;
        $provisioningFolder = $this->projectConfiguration->get('provisioning.folder_name');
        $dockerComposeFileName = $this->projectConfiguration->get('docker.compose_filename');
        $dockerComposeFileFolder = NovaCollection([$currentPwd, $provisioningFolder, $this->environment])->implode(
            '/'
        );

        if (!$fs->exists($dockerComposeFileFolder."/{$dockerComposeFileName}")) {
            throw new RuntimeException("There is no {$dockerComposeFileName} in {$dockerComposeFileFolder}");
        }
        $options = [
            'compose-file' => $dockerComposeFileFolder."/{$dockerComposeFileName}",
            'network-name' => $this->projectConfiguration->get('docker.network_name'),
            'network-prefix-port' => $this->projectConfiguration->get('docker.network_prefix_port'),
            'host-machine-mapping' => $this->projectConfiguration->get('docker.host_machine_mapping'),
            'project-path' => $this->projectPath,
            'provisioning-folder-name' => $provisioningFolder,
            'composer-cache-dir' => $this->projectConfiguration->get('docker.host_composer_cache_dir'),
            'project-cms-path-container' => $versionConfig->cmsRoot,
            'project-session-handler' => $versionConfig->sessionHandler,
            'console-path' => $versionConfig->consolePath,
        ];

        $this->dockerClient = new Docker($options, new ProcessRunner(), $this->optimizer);
        $this->taskExecutor = new TaskExecutor(
            $this->dockerClient,
            $this->projectConfiguration,
            $this->requiredRecipes,
            $input->getOption('docker-env')
        );
    }
}
