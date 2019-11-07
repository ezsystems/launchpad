<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Tests\Command;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Command
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('test')->setDescription('Test');
        $this->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io->writeln('Test1');
        $this->io->writeln('Test2');
        $this->io->writeln('Test3');
    }
}
