<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Listener;

use Symfony\Component\Console\Event\ConsoleErrorEvent;

final class CommandException
{
    public function onExceptionAction(ConsoleErrorEvent $event): void
    {
        //@todo logs
    }
}
