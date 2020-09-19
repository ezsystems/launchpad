<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Platformsh;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Deploy extends Command
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('platformsh:deploy')->setDescription('Deploy with Platformsh integration.');
        $this->setAliases(['psh:deploy']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $this->io->title($this->getDescription());

        // add a test to see if folder exists or not
        if (!$fs->exists("{$this->projectPath}/.platform")) {
            $this->io->error('Your project is not Platformsh ready.');
            $this->io->comment('Run <comment>~/ez platformsh:setup</comment> to set up the integration.');

            return Command::FAILURE;
        }

        $this->io->writeln(
            "There is no such thing with Platform.sh.\n".
            'To deploy you just have to <comment>git push</comment> in the good'.
            " <comment>remote</comment> (usually: platform)\n".
            "Automatically Platform.sh will trigger the deployment.\n".
            "\nIf you just want to manage one unique git repository have a look: \n".
            "\t<fg=cyan>Github Integration</>:\t https://docs.platform.sh/administration/integrations/github.html\n".
            "\t<fg=cyan>Bitbucket Integration</>:\t https://docs.platform.sh/administration/integrations/bitbucket.html"
        );

        return Command::SUCCESS;
    }
}
