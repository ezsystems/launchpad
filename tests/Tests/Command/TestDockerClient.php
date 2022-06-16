<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Tests\Command;

use eZ\Launchpad\Configuration\CmsVersionRegistry;
use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestDockerClient extends DockerCommand
{

    public function __construct()
    {
        parent::__construct(new CmsVersionRegistry());
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('testdockerclient')->setDescription('Test Docker Client');
        $this->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io->writeln('Test1');
        $this->io->writeln('Test2');
        $this->io->writeln('Test3');
    }
}
