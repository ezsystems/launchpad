<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Listener;

use Carbon\Carbon;
use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class ApplicationUpdate
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    public function __construct(array $parameters, ProjectConfiguration $configuration)
    {
        $this->parameters = $parameters;
        $this->projectConfiguration = $configuration;
    }

    public function onCommandAction(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command instanceof BaseCommand) {
            return;
        }

        if (\in_array($command->getName(), ['self-update', 'rollback'])) {
            return;
        }

        $authorized = [
            'list', 'help', 'test', 'docker:initialize:skeleton', 'docker:initialize', 'docker:create',
            'self-update', 'rollback',
        ];
        if (!\in_array($command->getName(), $authorized)) {
            $fs = new Filesystem();
            $currentPwd = getcwd();
            $provisioningFolder = $this->projectConfiguration->get('provisioning.folder_name');
            $dockerComposeFileName = $this->projectConfiguration->get('docker.compose_filename');
            $dockerEnv = $event->getInput()->hasOption('env') ? $event->getInput()->getOption(
                'env'
            ) : 'dev';
            $dockerComposeFileFolder = NovaCollection(
                [$currentPwd, $provisioningFolder, $dockerEnv]
            )->implode(
                '/'
            );

            if (!$fs->exists($dockerComposeFileFolder."/{$dockerComposeFileName}")) {
                $io = new SymfonyStyle($event->getInput(), $event->getOutput());
                $io->error('Your are not in a folder managed by eZ Launchpad.');
                $event->disableCommand();
                $event->stopPropagation();

                return;
            }
        }

        // do not check anything  on Github Actions
        if (false !== getenv('GITHUB_ACTIONS')) {
            return;
        }

        // check last time check
        if (null != $this->projectConfiguration->get('last_update_check')) {
            $lastUpdate = Carbon::createFromTimestamp($this->projectConfiguration->get('last_update_check'));
            /** @var Carbon $lastUpdate */
            $now = Carbon::now();
            if ($now > $lastUpdate->subDays(3)) {
                return;
            }
        }

        $releaseUrl = $this->parameters['release_url'];
        $release = githubFetch($releaseUrl)[0];
        $currentVersion = normalizeVersion($command->getApplication()->getVersion());
        $lastVersion = normalizeVersion($release->tag_name);

        if ($lastVersion > $currentVersion) {
            $io = new SymfonyStyle($event->getInput(), $event->getOutput());
            $io->note('A new version of eZ Launchpad is available, please run self-update.');
            sleep(2);
        }

        if (!\in_array($command->getName(), ['list', 'help'])) {
            $this->projectConfiguration->setLocal('last_update_check', time());
        }
    }
}
