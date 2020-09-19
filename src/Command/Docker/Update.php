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

final class Update extends DockerCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:update')->setDescription('Update to last images.');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Image service to update.', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerClient->pull(['--ignore-pull-failures'], $input->getArgument('service'));
        $this->dockerClient->build([], $input->getArgument('service'));
        $this->dockerClient->up(['-d'], $input->getArgument('service'));
        $this->taskExecutor->composerInstall();

        return DockerCommand::SUCCESS;
    }
}
