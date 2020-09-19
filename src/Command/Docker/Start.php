<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Start extends DockerCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:start')->setDescription('Start all the services (or just one).');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Service to start', '');
        $this->setAliases(['start']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerClient->start($input->getArgument('service'));

        return DockerCommand::SUCCESS;
    }
}
