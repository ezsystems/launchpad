<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core\OSX\Optimizer;

use Symfony\Component\Console\Style\SymfonyStyle;

interface NFSAwareInterface
{
    /**
     * File to set the NFS exports.
     */
    public const EXPORTS = '/etc/exports';

    /**
     * File to set the nfs.server.mount.require_resv_port = 0.
     */
    public const RESV = '/etc/nfs.conf';

    public function restartNFSD(SymfonyStyle $io): bool;

    public function getHostMapping(): array;

    public function isExportReady($export): bool;

    public function isResvReady(): bool;

    public function setupNFS(SymfonyStyle $io, $exportOptions): bool;

    public function standardNFSConfigurationMessage(SymfonyStyle $io, string $exportLine): void;

    public function getExportOptions(): string;
}
