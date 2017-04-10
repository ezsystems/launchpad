<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SymfonyRun.
 */
class SymfonyRun extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:sfrun')->setDescription('Run a Symfony command in the engine.');
        $this->setAliases(['sfrun']);
        $this->addArgument('sfcommand', InputArgument::IS_ARRAY, 'Symfony Command to run in');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $allArugments = $input->getArgument('sfcommand');

        // @todo: find a way to pass the options
        $options = '';

        $this->dockerClient->exec(
            '/var/www/html/project/ezplatform/app/console '.implode(' ', $allArugments)." {$options}",
            [],
            'engine'
        );
    }
}
