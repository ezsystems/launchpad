<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DumpData.
 */
class DumpData extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:dumpdata')->setDescription('Dump Database and Storage.');
        $this->setAliases(['dumpdata']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $fs->mkdir("{$this->projectPath}/data");

        $this->dockerClient->exec(
            '/var/www/html/project/create_dump.bash',
            [
                '--user', 'www-data',
            ],
            'engine'
        );
    }
}
