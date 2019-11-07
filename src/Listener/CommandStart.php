<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Listener;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Filesystem\Filesystem;

final class CommandStart
{
    public function onCommandAction(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof Command) {
            return;
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
