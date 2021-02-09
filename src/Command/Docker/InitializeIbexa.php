<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\Client\Docker;
use eZ\Launchpad\Core\DockerCompose;
use eZ\Launchpad\Core\ProjectWizardIbexa;
use eZ\Launchpad\Core\ProjectWizardInterface;
use eZ\Launchpad\Core\TaskExecutor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class InitializeIbexa extends Initialize
{
    protected function configure(): void
    {
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
        $initialdata = $input->getArgument('initialdata');
        $normalizedVersion = trim($input->getArgument('version'), 'v');
        $package = $this->projectWizard->getMode();
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

    public function setProjectWizard(): ProjectWizardInterface
    {
        $this->projectWizard = new ProjectWizardIbexa($this->io, $this->projectConfiguration);

        return $this->projectWizard;
    }
}
