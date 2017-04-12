<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Tests\Command;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TestDockerClient.
 */
class TestDockerClient extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('testdockerclient')->setDescription('Test Docker Client');
        $this->setHidden(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->writeln('Test1');
        $this->io->writeln('Test2');
        $this->io->writeln('Test3');
    }
}
