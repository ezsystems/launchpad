<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Console\Application;
use eZ\Launchpad\Core\Command;
use Phar;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /* @var Application $application */
        $application = $this->getApplication();
        $output->writeln($application->getLogo());

        $releaseUrl = $this->parameters['release_url'];
        $releases = githubFetch($releaseUrl);
        if (null === $releases) {
            $this->io->comment('Cannot find new releases, please try later.');

            return Command::FAILURE;
        }
        $release = $releases[0];
        $currentVersion = normalizeVersion($application->getVersion());
        $lastVersion = normalizeVersion($release->tag_name);
        if ($lastVersion <= $currentVersion) {
            $this->io->comment('No update is required! You have the last version!');

            return Command::FAILURE;
        }

        $localPharFile = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        $localPharDir = \dirname($localPharFile);
        $backPharFile = $localPharDir.'/ez.phar.backup';
        copy($localPharFile, $backPharFile);
        $assetUrl = $release->assets[0]->browser_download_url;
        $tempPharFile = $localPharDir.'/ez.phar.temp';
        file_put_contents($tempPharFile, githubFetch($assetUrl, false));
        copy($localPharFile.'.pubkey', $tempPharFile.'.pubkey');

        $phar = new Phar($tempPharFile);
        $signature = $phar->getSignature();
        if ('openssl' !== strtolower($signature['hash_type'])) {
            $this->io->error('Invalid Signature.');

            return Command::FAILURE;
        }
        rename($tempPharFile, $localPharFile);
        chmod($localPharFile, fileperms($backPharFile));
        unlink($tempPharFile.'.pubkey');
        $this->io->writeln(
            "Updated from <info>{$application->getVersion()}</info> to <info>{$release->tag_name}</info>."
        );

        return Command::SUCCESS;
    }
}
