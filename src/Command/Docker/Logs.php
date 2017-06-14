<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Logs.
 */
class Logs extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:logs')->setDescription('Display the logs.');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Service to log.', '');
        $this->setAliases(['logs', 'log']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dockerClient->logs(['-f', '--tail=100'], $input->getArgument('service'));
    }
}
