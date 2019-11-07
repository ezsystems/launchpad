<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

interface OptimizerInterface
{
    public function isEnabled(): bool;

    public function hasPermission(SymfonyStyle $io): bool;

    public function optimize(SymfonyStyle $io, Command $command);

    public function supports(int $version): bool;
}
