<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\OSX\Optimizer;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface NFSAwareInterface.
 */
interface NFSAwareInterface
{
    /**
     * File to set the NFS exports.
     */
    const EXPORTS = '/etc/exports';

    /**
     * File to set the nfs.server.mount.require_resv_port = 0.
     */
    const RESV = '/etc/nfs.conf';

    /**
     * @return bool
     */
    public function restartNFSD(SymfonyStyle $io);

    /**
     * @return array
     */
    public function getHostMapping();

    /**
     * @return bool
     */
    public function isExportReady($export);

    /**
     * @return bool
     */
    public function isResvReady();

    public function setupNFS(SymfonyStyle $io, $exportOptions);

    /**
     * @param string $exportLine
     */
    public function standardNFSConfigurationMessage(SymfonyStyle $io, $exportLine);

    /**
     * @return string
     */
    public function getExportOptions();
}
