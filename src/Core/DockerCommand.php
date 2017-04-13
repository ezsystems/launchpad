<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Core\Client\Docker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DockerCommand.
 */
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('env', 'env', InputOption::VALUE_REQUIRED, 'Docker Env', 'dev');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->environment = $input->getOption('env');

        $fs                      = new Filesystem();
        $currentPwd              = $this->projectPath;
        $provisioningFolder      = $this->projectConfiguration->get('provisioning.folder_name');
        $dockerComposeFileName   = $this->projectConfiguration->get('docker.compose_filename');
        $dockerComposeFileFolder = NovaCollection([$currentPwd, $provisioningFolder, $this->environment])->implode(
            '/'
        );

        if (!$fs->exists($dockerComposeFileFolder."/{$dockerComposeFileName}")) {
            throw new \RuntimeException("There is no {$dockerComposeFileName} in {$dockerComposeFileFolder}");
        }
        $options = [
            'compose-file'             => $dockerComposeFileFolder."/{$dockerComposeFileName}",
            'network-name'             => $this->projectConfiguration->get('docker.network_name'),
            'network-prefix-port'      => $this->projectConfiguration->get('docker.network_prefix_port'),
            'host-machine-mapping'     => $this->projectConfiguration->get('docker.host_machine_mapping'),
            'project-path'             => $this->projectPath,
            'provisioning-folder-name' => $provisioningFolder,
            'composer-cache-dir'       => $this->projectConfiguration->get('docker.host_composer_cache_dir'),
        ];

        $this->dockerClient = new Docker($options);
        $this->taskExecutor = new TaskExecutor(
            $this->dockerClient,
            $this->projectConfiguration,
            $this->requiredRecipes
        );
    }
}
