<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Configuration\CmsVersionRegistry;
use eZ\Launchpad\Core\DockerCommand;
use eZ\Launchpad\Core\ProjectStatusDumper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Status extends DockerCommand
{
    /**
     * @var ProjectStatusDumper
     */
    protected $projectStatusDumper;

    public function __construct(CmsVersionRegistry $cmsVersionRegistry, ProjectStatusDumper $projectStatusDumper)
    {
        parent::__construct($cmsVersionRegistry);
        $this->projectStatusDumper = $projectStatusDumper;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:status')->setDescription('Obtaining the project information.');
        $this->setAliases(['docker:ps', 'docker:info', 'ps', 'info']);
        $this->addArgument(
            'options',
            InputArgument::OPTIONAL,
            'n: Docker Network, c: Docker Compose, s: Service Access',
            'ncsz'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setDockerClient($this->dockerClient);
        $this->projectStatusDumper->setIo($this->io);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->projectStatusDumper->dump($input->getArgument('options'));

        return DockerCommand::SUCCESS;
    }
}
