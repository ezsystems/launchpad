<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface OptimizerInterface.
 */
interface OptimizerInterface
{
    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @param SymfonyStyle $io
     *
     * @return bool
     */
    public function hasPermission(SymfonyStyle $io);

    /**
     * @param Command $command
     *
     * @return bool
     */
    public function init(Command $command);

    /**
     * @param SymfonyStyle $io
     * @param Command      $command
     *
     * @return mixed
     */
    public function optimize(SymfonyStyle $io, Command $command);

    /**
     * @param int $version
     *
     * @return bool
     */
    public function supports($version);
}
