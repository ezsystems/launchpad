<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Clean.
 */
class Clean extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:clean')->setDescription('Clean all the services.');
        $this->setAliases(['docker:down', 'clean', 'down']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dockerClient->down(['-v', '--remove-orphans']);
    }
}
