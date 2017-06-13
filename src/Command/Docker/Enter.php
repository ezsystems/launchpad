<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Enter.
 */
class Enter extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:enter')->setDescription('Enter in a container.');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Service to enter in', 'engine');
        $this->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User with who to enter in', 'www-data');
        $this->addArgument('shell', InputArgument::OPTIONAL, 'Command to enter in', '/bin/bash');
        $this->setAliases(['enter', 'docker:exec', 'exec']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dockerClient->exec(
            $input->getArgument('shell'),
            [
                '--user', $input->getOption('user'),
            ],
            $input->getArgument('service')
        );
    }
}
