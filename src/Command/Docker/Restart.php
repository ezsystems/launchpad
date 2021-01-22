<?php

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Restart.
 */
final class Restart extends DockerCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:restart')->setDescription('Restart all the services (or just one).');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Service to restart', '');
        $this->setAliases(['restart']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerClient->restart($input->getArgument('service'));

        return DockerCommand::SUCCESS;
    }
}
