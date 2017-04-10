<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Console\Application;
use eZ\Launchpad\Core\Command;
use Humbug\SelfUpdate\Strategy\ShaStrategy;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SelfUpdate.
 */
class SelfUpdate extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('self-update')->setDescription('Self Update');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        $app_env = $application->getContainer()->getParameter('app_env');
        $app_dir = $application->getContainer()->getParameter('app_dir');

        $localPharFile = $app_env == 'prod' ? null : $app_dir.'/docs/ez.phar';
        $updater       = new Updater($localPharFile);
        $strategy      = $updater->getStrategy();
        if ($strategy instanceof ShaStrategy) {
            if ($app_env == 'prod') {
                $ez_phar         = $application->getContainer()->getParameter('ez_phar');
                $ez_phar_version = $application->getContainer()->getParameter('ez_phar_version');
                $strategy->setPharUrl($ez_phar);
                $strategy->setVersionUrl($ez_phar_version);
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
