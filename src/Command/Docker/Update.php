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
 * Class Update.
 */
class Update extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:update')->setDescription('Update to last images.');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Image service to update.', '');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dockerClient->pull(['--ignore-pull-failures'], $input->getArgument('service'));
        $this->dockerClient->build([], $input->getArgument('service'));
        $this->dockerClient->up(['-d'], $input->getArgument('service'));
    }
}
