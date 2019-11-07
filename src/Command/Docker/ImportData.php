<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportData extends DockerCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:importdata')->setDescription('Import Database and Storage.');
        $this->setAliases(['importdata']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->taskExecutor->importData();
    }
}
