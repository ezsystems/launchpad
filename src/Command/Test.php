<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Test.
 */
class Test extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('test')->setDescription('Test');
        $this->setHidden(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->writeln('Test1');
        $this->io->writeln('Test2');
        $this->io->writeln('Test3');
    }
}
