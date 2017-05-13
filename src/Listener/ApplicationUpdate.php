<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Listener;

use Carbon\Carbon;
use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use Humbug\SelfUpdate\Strategy\ShaStrategy;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class ApplicationUpdate
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * ApplicationUpdate constructor.
     *
     * @param $configuration
     */
    public function __construct($parameters, ProjectConfiguration $configuration)
    {
        $this->parameters           = $parameters;
        $this->projectConfiguration = $configuration;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommandAction(ConsoleCommandEvent $event)
    {
        $env     = $this->parameters['env'];
        $dir     = $this->parameters['dir'];
        $url     = $this->parameters['url'];
        $version = $this->parameters['version'];

        $command = $event->getCommand();
        if (in_array($command->getName(), ['self-update', 'rollback'])) {
            return;
        }

        if (!in_array(
            $command->getName(),
            ['list', 'help', 'test', 'docker:initialize', 'docker:create', 'self-update', 'rollback']
        )
        ) {
            $fs                      = new Filesystem();
            $currentPwd              = getcwd();
            $provisioningFolder      = $this->projectConfiguration->get('provisioning.folder_name');
            $dockerComposeFileName   = $this->projectConfiguration->get('docker.compose_filename');
            $dockerEnv               = $event->getInput()->hasOption('env') ? $event->getInput()->getOption(
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

        // check last time check
        if ($this->projectConfiguration->get('last_update_check') != null) {
            $lastUpdate = Carbon::createFromTimestamp($this->projectConfiguration->get('last_update_check'));
            $now        = Carbon::now();
            if ($now > $lastUpdate->subDays(3)) {
                return;
            }
        }

        $localPharFile = $env == 'prod' ? null : $dir.'/docs/ez.phar';
        $updater       = new Updater($localPharFile);
        $strategy      = $updater->getStrategy();
        if ($strategy instanceof ShaStrategy) {
            $strategy->setPharUrl($url);
            $strategy->setVersionUrl($version);
            if ($updater->hasUpdate()) {
                $io = new SymfonyStyle($event->getInput(), $event->getOutput());
                $io->note('A new version of eZ Launchpad is available, please run self-update.');
                sleep(2);
            }
        }

        if (!in_array($command->getName(), ['list', 'help'])) {
            $this->projectConfiguration->setLocal('last_update_check', time());
        }
    }
}
