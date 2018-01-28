<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Command\Docker\Initialize as InitializeCommand;
use eZ\Launchpad\Core\Client\DockerSync as DockerSyncClient;
use eZ\Launchpad\Core\Command;
use eZ\Launchpad\Core\DockerCommand;
use Humbug\SelfUpdate\Exception\RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DockerSync.
 */
class DockerSync extends Optimizer implements OptimizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return DockerSyncClient::isOn();
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission(SymfonyStyle $io)
    {
        $io->caution('You are on Mac OS X, for optimal performance we recommend to use Docker Sync.');
        $io->comment(
            '
This wizard is going to check and to do this step if required:
- Use the <comment>docker-sync.yml</comment> file in <comment>your provisioning folder.</comment>
- Create the related volumes useful for <comment>Docker Sync</comment> using <comment>docker-sync.yml</comment>
- Run the docker-sync daemon
'
        );

        return $io->confirm('Do you want to use Docker Sync?');
    }

    /**
     * {@inheritdoc}
     */
    public function init(Command $command)
    {
        if ($command instanceof DockerCommand) {
            $command->enabledDockerSyncClient();
        }

        if ($command instanceof InitializeCommand) {
            $command->enabledDockerSyncClient();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function optimize(SymfonyStyle $io, Command $command)
    {
        if (!$this->isDockerSyncInstalled()) {
            throw new RuntimeException('To use docker-sync you need to install it first.');
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isDockerSyncInstalled()
    {
        exec('which -s docker-sync', $output, $return);

        return 0 === $return;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($version)
    {
        return true;
    }
}
