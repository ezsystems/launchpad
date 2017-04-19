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
 * Class ComposerRun.
 */
class ComposerRun extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:comprun')->setDescription('Run Composer command in the engine.');
        $this->setAliases(['comprun']);
        $this->addArgument(
            'compcommand',
            InputArgument::IS_ARRAY,
            'Composer Command to run in. Use "" to pass options.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $allArguments = $input->getArgument('compcommand');
        $options      = '';
        $this->taskExecutor->runComposerCommand(implode(' ', $allArguments)." {$options}");
    }
}
