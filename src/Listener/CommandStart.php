<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Listener;

use eZ\Launchpad\Core\Command;
use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class CommandStart
{
    public function onCommandAction(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof Command) {
            return;
        }

        // Ensure that docker is running
        $nonDockerCommandCheckList = [
            'docker:initialize:skeleton', 'docker:initialize'
        ];
        if (($command instanceof DockerCommand) || (\in_array($command->getName(), $nonDockerCommandCheckList))) {
            $output = $return = null;
            exec('docker system info > /dev/null 2>&1', $output, $return);
            if (0 !== $return) {
                $io = new SymfonyStyle($event->getInput(), $event->getOutput());
                $io->error('You need to start Docker before to run that command.');
                $event->disableCommand();
                $event->stopPropagation();

                return;
            }
        }

        $fs = new Filesystem();
        $command->getRequiredRecipes()->each(
            function ($recipe) use ($fs, $command) {
                $fs->copy(
                    "{$command->getPayloadDir()}/recipes/{$recipe}.bash",
                    "{$command->getProjectPath()}/{$recipe}.bash",
                    true
                );
                $fs->chmod("{$command->getProjectPath()}/{$recipe}.bash", 0755);
            }
        );
    }
}
