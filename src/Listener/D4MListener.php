<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Listener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class D4MListener
{
    /**
     * File to set the NFS exports
     */
    CONST EXPORTS = "/etc/exports";

    /**
     * File to set the nfs.server.mount.require_resv_port = 0
     */
    CONST RESV = "/etc/nfs.conf";

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommandAction(ConsoleCommandEvent $event)
    {
        if (php_uname('s') != 'Darwin') {
            return;
        }
        $command = $event->getCommand();
        // don't bother for those command
        if (in_array($command->getName(), ['self-update', 'rollback', 'list', 'help', 'test'])) {
            return;
        }
        $io = new SymfonyStyle($event->getInput(), $event->getOutput());

        // what do we need here
        // check the Moby VM is on
        // check we can touch it
        // check the etc export
        // check that we are in a folder exported

        $io->section("PLOP");

        $this->isMacNfsServerSate();

        $event->disableCommand();

        return;

    }

    protected function isMacNfsServerSate()
    {
        $fs = new Filesystem();
        if (!$fs->exists(static::EXPORTS)) {
            return false;
        }

        if (!$fs->exists(static::RESV)) {
            return false;
        }

        $isNfsConfReady = NovaCollection(file(static::RESV))->exists(
            function ($line) {
                $line = trim($line);
                if (preg_match("/^nfs\.server\.mount\.require_resv_port/", $line)) {
                    if (strpos($line, "=")) {
                        return (string) explode("=", $line)[1] == '0';
                    }
                }

                return false;
            }
        );

        if (!$isNfsConfReady) {
            return false;
        }




        //
        //
        //
        //        $exportContentLines = file(static::EXPORTS);
        //        $exports = NovaCollection([]);
        //        foreach ($exportContentLines as $line) {
        //            $parts = explode(" ", $line);
        //
        //
        //        }

    }
}
