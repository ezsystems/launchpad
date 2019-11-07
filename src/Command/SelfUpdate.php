<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Console\Application;
use eZ\Launchpad\Core\Command;
use Humbug\SelfUpdate\Strategy\ShaStrategy;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdate extends Command
{
    /**
     * @var array
     */
    protected $parameters;

    protected function configure(): void
    {
        $this->setName('self-update')->setDescription('Self Update');
    }

    public function setParameters(array $parameters = []): void
    {
        $this->parameters = $parameters;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        $app_env = $this->parameters['env'];
        $app_dir = $this->appDir;

        $localPharFile = 'prod' === $app_env ? null : $app_dir.'/docs/ez.phar';
        $updater = new Updater($localPharFile);
        $strategy = $updater->getStrategy();
        if ($strategy instanceof ShaStrategy) {
            if ('prod' === $app_env) {
                $strategy->setPharUrl($this->parameters['url']);
                $strategy->setVersionUrl($this->parameters['version']);
            }
            $result = $updater->update();
            $this->io->section('eZ Launchpad Auto Update');
            if (!$result) {
                $this->io->comment('No update is required! You have the last version!');
            } else {
                $new = $updater->getNewVersion();
                $old = $updater->getOldVersion();
                $this->io->writeln("Updated from <info>{$old}</info> to <info>{$new}</info>.");
            }
        } else {
            $this->io->error('Strategy not implemented.');
        }
    }
}
