<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    /**
     * @return Process|array
     */
    public function run(string $command, array $envVars)
    {
        $process = Process::fromShellCommandline($command, null, $envVars);
        $process->setTimeout(2 * 3600);

        if ($this->hasTty()) {
            $process->setTty(true);
        }

        try {
            return $process->mustRun();
        } catch (ProcessFailedException $e) {
            $authorizedExitCodes = [
                129, // Hangup
                130, // Interrupt
            ];

            if (!\in_array($e->getProcess()->getExitCode(), $authorizedExitCodes)) {
                throw $e;
            }
        }

        return null;
    }

    public function hasTty(): bool
    {
        // Extracted from \Symfony\Component\Process\Process::setTty
        if ('\\' === \DIRECTORY_SEPARATOR) {
            // Windows platform does not have TTY
            $isTtySupported = false;
        } else {
            $pipes = null;
            // TTY mode requires /dev/tty to be read/writable.
            $isTtySupported = (bool) @proc_open(
                'echo 1 >/dev/null',
                [
                    ['file', '/dev/tty', 'r'],
                    ['file', '/dev/tty', 'w'],
                    ['file', '/dev/tty', 'w'],
                ],
                $pipes
            );
        }

        return $isTtySupported;
    }
}
