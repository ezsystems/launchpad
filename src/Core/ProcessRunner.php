<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    /**
     * @param string $command
     * @param array  $envVars
     *
     * @return Process
     */
    public function run($command, $envVars)
    {
        $process = new Process(escapeshellcmd($command), null, $envVars);
        if ('WIN' !== strtoupper(substr(PHP_OS, 0, 3))) {
            $process->setTty(true);
        }
        $process->setTimeout(2 * 3600);
        try {
            return $process->mustRun();
        } catch (ProcessFailedException $e) {
            $authorizedExitCodes = [
                129, // Hangup
                130, // Interrupt
            ];

            if (!in_array($e->getProcess()->getExitCode(), $authorizedExitCodes)) {
                throw $e;
            }
        }

        return null;
    }
}
