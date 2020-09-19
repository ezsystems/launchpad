<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Platformsh;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Setup extends DockerCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('platformsh:setup')->setDescription('Set up the Platformsh integration.');
        $this->setAliases(['psh:setup']);
    }

    protected function postAction(): void
    {
        $this->io->writeln(
            'You can also look at <comment>~/ez platformsh:deploy</comment>.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $this->io->title($this->getDescription());

        // add a test to see if folder exists or not
        if ($fs->exists("{$this->projectPath}/.platform")) {
            if (!$this->io->confirm('You already have a <comment>.platform</comment> folder, continue?')) {
                $this->postAction();

                return DockerCommand::FAILURE;
            }
        }

        // Dump the project
        $this->taskExecutor->dumpData();

        // move it to the top folder as required my Platform.sh
        $fs->rename("{$this->projectPath}/ezplatform/.platform", "{$this->projectPath}/.platform");

        $this->io->writeln(
            "Your project is now set up with Platform.sh.\n".
            "You can run <comment>git status</comment> to see the changes\n".
            "Then you just have to \n".
            "\t<comment>git add .</comment>\n".
            "\t<comment>git commit -m \"Integration Platform.sh\"</comment>\n".
            "\t<comment>git push platform {branchname}</comment>\n"
        );

        $this->postAction();

        return DockerCommand::SUCCESS;
    }
}
