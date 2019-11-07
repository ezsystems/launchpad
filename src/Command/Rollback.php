<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Core\Command;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Rollback extends Command
{
    protected function configure(): void
    {
        $this->setName('rollback')->setDescription('Rollback an update.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $updater = new Updater();
        try {
            $result = $updater->rollback();
            if ($result) {
                $this->io->success('Rollbacked!');
            } else {
                $this->io->error('The rollback was not possible.');
            }
        } catch (\Exception $e) {
            $this->io->error('Well, something happened! Either an oopsie or something involving hackers.');
        }
    }
}
