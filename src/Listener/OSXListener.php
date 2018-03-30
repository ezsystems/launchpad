<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Listener;

use eZ\Launchpad\Core\Command;
use eZ\Launchpad\Core\OSX\Optimizer\D4M;
use eZ\Launchpad\Core\OSX\Optimizer\OptimizerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

class OSXListener
{
    /**
     * @var array
     */
    protected $optimizers;

    /**
     * OSXListener constructor.
     *
     * @param D4M $d4mOptimizer
     */
    public function __construct(D4M $d4mOptimizer)
    {
        $this->optimizers = [$d4mOptimizer];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommandAction(ConsoleCommandEvent $event)
    {
        if (!EZ_ON_OSX) {
            return;
        }
        /** @var Command $command */
        $command = $event->getCommand();

        // don't bother for those command
        if (in_array($command->getName(), ['self-update', 'rollback', 'list', 'help', 'test'])) {
            return;
        }

        $io = new SymfonyStyle($event->getInput(), $event->getOutput());

        try {
            $version = $this->getDockerVersion();
            foreach ($this->optimizers as $optimizer) {
                /** @var OptimizerInterface $optimizer */
                if ($optimizer->supports($version) &&
                    $optimizer->init($command) &&
                    !$optimizer->isEnabled() &&
                    $optimizer->hasPermission($io)) {
                    $optimizer->optimize($io, $command);
                    // only one allowed
                    break;
                }
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            $event->disableCommand();
            $event->stopPropagation();

            return;
        }

        return;
    }

    /**
     * @return int
     */
    protected function getDockerVersion()
    {
        exec('docker -v 2>/dev/null', $output, $return);
        if (0 !== $return) {
            throw new \RuntimeException('You need to install Docker for Mac before to run that command.');
        }
        list($version, $build) = explode(',', $output[0]);
        unset($build);
        $result                      = preg_replace('/([^ ]*) ([^ ]*) ([0-9\\.]*)-([a-zA-z]*)/ui', '$3', $version);
        list($major, $minor, $patch) = explode('.', $result);
        unset($patch);
        $normalizedVersion = (int) $major * 100 + $minor;

        return (int) $normalizedVersion;
    }
}
