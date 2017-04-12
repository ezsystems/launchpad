<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Listener;

use Symfony\Component\Console\Event\ConsoleErrorEvent;

/**
 * Class CommandException.
 */
class CommandException
{
    /**
     * @param ConsoleErrorEvent $event
     */
    public function onExceptionAction(ConsoleErrorEvent $event)
    {
        //@todo logs
    }
}
