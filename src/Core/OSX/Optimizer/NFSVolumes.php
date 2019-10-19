<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Core\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class D4MListener.
 */
class NFSVolumes extends Optimizer implements OptimizerInterface, NFSAwareInterface
{
    use NFSTrait;

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        list($export, $mountPoint) = $this->getHostMapping();
        $export                    = MacOSPatherize($export);

        return $this->isResvReady() && $this->isExportReady($export);
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission(SymfonyStyle $io)
    {
        list($export, $mountPoint) = $this->getHostMapping();
        $export                    = MacOSPatherize($export);
        $exportLine                = "{$export} {$this->getExportOptions()}";
        $this->standardNFSConfigurationMessage($io, $exportLine);

        return $io->confirm('Do you want to setup your Mac as an NFS server?');
    }

    /**
     * {@inheritdoc}
     */
    public function getExportOptions()
    {
        return '-mapall='.getenv('USER').':staff -alldirs localhost';
    }

    /**
     * {@inheritdoc}
     */
    public function optimize(SymfonyStyle $io, Command $command)
    {
        $this->setupNFS($io, $this->getExportOptions());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($version)
    {
        return $version >= 1803;
    }
}
