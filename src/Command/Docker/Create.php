<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use eZ\Launchpad\Core\ProjectStatusDumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Create extends DockerCommand
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

    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:create')->setDescription('Create all the services.');
        $this->setAliases(['create']);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setDockerClient($this->dockerClient);
        $this->projectStatusDumper->setIo($this->io);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->dockerClient->build(['--no-cache']);
        $this->dockerClient->up(['-d']);

        $this->taskExecutor->composerInstall();
        $this->taskExecutor->eZCreate();
        $this->taskExecutor->importData();

        // if solr run the index
        $compose = $this->projectConfiguration->getDockerCompose();
        if ($compose->hasService('solr')) {
            $this->taskExecutor->createCore();
            $this->taskExecutor->indexSolr();
        }

        $this->projectStatusDumper->dump('ncsi');
    }
}
