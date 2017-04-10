<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Status.
 */
class Status extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:status')->setDescription('Obtaining the project information.');
        $this->setAliases(['docker:ps', 'docker:info', 'ps', 'info']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composeCommand = $this->dockerClient->getComposeCommand();
        $this->io->section('Network');
        $this->dockerClient->ps();
        $this->io->section("\nDocker Compose command");
        $this->io->writeln($composeCommand);
        $this->io->section("\nService Access");
    }
}
