<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use eZ\Launchpad\Core\ProjectStatusDumper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Status.
 */
class Status extends DockerCommand
{
    /**
     * @var ProjectStatusDumper
     */
    protected $projectStatusDumper;

    /**
     * Status constructor.
     *
     * @param ProjectStatusDumper $projectStatusDumper
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
        $this->setName('docker:status')->setDescription('Obtaining the project information.');
        $this->setAliases(['docker:ps', 'docker:info', 'ps', 'info']);
        $this->addArgument(
            'options',
            InputArgument::OPTIONAL,
            'n: Docker Network, c: Docker Compose, s: Service Access, z: Docker Sync',
            'ncsz'
        );
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
        $this->projectStatusDumper->dump($input->getArgument('options'));
    }
}
