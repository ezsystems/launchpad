<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use eZ\Launchpad\Core\ProjectStatusDumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Create.
 */
class Create extends DockerCommand
{
    /**
     * @var ProjectStatusDumper
     */
    protected $projectStatusDumper;

    /**
     * Status constructor.
     */
    public function __construct(ProjectStatusDumper $projectStatusDumper)
    {
        parent::__construct();
        $this->projectStatusDumper = $projectStatusDumper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:create')->setDescription('Create all the services.');
        $this->setAliases(['create']);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setDockerClient($this->dockerClient);
        $this->projectStatusDumper->setIo($this->io);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
