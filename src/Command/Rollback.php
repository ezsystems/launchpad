<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Core\Command;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Rollback.
 */
class Rollback extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('rollback')->setDescription('Rollback an update.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
