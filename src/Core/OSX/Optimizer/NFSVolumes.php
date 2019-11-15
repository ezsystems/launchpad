<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

class NFSVolumes extends Optimizer implements OptimizerInterface, NFSAwareInterface
{
    use NFSTrait;

    public function isEnabled(): bool
    {
        list($export, $mountPoint) = $this->getHostMapping();
        $export = MacOSPatherize($export);

        return $this->isResvReady() && $this->isExportReady($export);
    }

    public function hasPermission(SymfonyStyle $io): bool
    {
        list($export, $mountPoint) = $this->getHostMapping();
        $export = MacOSPatherize($export);
        $exportLine = "{$export} {$this->getExportOptions()}";
        $this->standardNFSConfigurationMessage($io, $exportLine);

        return $io->confirm('Do you want to setup your Mac as an NFS server?');
    }

    public function getExportOptions(): string
    {
        return '-mapall='.getenv('USER').':staff -alldirs localhost';
    }

    public function optimize(SymfonyStyle $io, Command $command): bool
    {
        $this->setupNFS($io, $this->getExportOptions());

        return true;
    }

    public function supports($version): bool
    {
        return $version >= 1803;
    }
}
