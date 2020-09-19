<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core\OSX\Optimizer;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

trait NFSTrait
{
    public function restartNFSD(SymfonyStyle $io): bool
    {
        $output = $returnCode = null;
        exec('sudo nfsd restart', $output, $returnCode);
        if (0 != $returnCode) {
            throw new RuntimeException('NFSD restart failed.');
        }
        $io->success('NFSD restarted.');

        return true;
    }

    public function getHostMapping(): array
    {
        $default = [getenv('HOME'), getenv('HOME')];

        $currentMapping = $this->projectConfiguration->get('docker.host_machine_mapping');
        if ($currentMapping) {
            return explode(':', $currentMapping);
        }
        $this->projectConfiguration->setGlobal('docker.host_machine_mapping', implode(':', $default));

        return $default;
    }

    public function isExportReady($export): bool
    {
        $fs = new Filesystem();
        if (!$fs->exists(NFSAwareInterface::EXPORTS)) {
            return false;
        }

        return NovaCollection(file(NFSAwareInterface::EXPORTS))->exists(
            function ($line) use ($export) {
                $export = addcslashes($export, '/.');

                $line = trim($line);

                return 1 === preg_match("#^{$export}#", $line);
            }
        );
    }

    public function isResvReady(): bool
    {
        $fs = new Filesystem();
        if (!$fs->exists(NFSAwareInterface::RESV)) {
            return false;
        }

        return NovaCollection(file(NFSAwareInterface::RESV))->exists(
            function ($line) {
                $line = trim($line);
                if (preg_match("/^nfs\.server\.mount\.require_resv_port/", $line)) {
                    if (strpos($line, '=')) {
                        return '0' == trim(explode('=', $line)[1]);
                    }
                }

                return false;
            }
        );
    }

    public function setupNFS(SymfonyStyle $io, $exportOptions): bool
    {
        list($export, $mountPoint) = $this->getHostMapping();
        $export = MacOSPatherize($export);

        $isResvReady = $this->isResvReady();
        $isExportReady = $this->isExportReady($export);

        if (!$isResvReady) {
            exec(
                'echo "nfs.server.mount.require_resv_port = 0" | sudo tee -a '.NFSAwareInterface::RESV,
                $output,
                $returnCode
            );
            if (0 != $returnCode) {
                throw new RuntimeException('Writing in '.NFSAwareInterface::RESV.' failed.');
            }
            $io->success(NFSAwareInterface::RESV.' updated.');
            if (!$this->restartNFSD($io)) {
                return false;
            }
        }

        if (!$isExportReady) {
            $output = $returnCode = null;
            $exportLine = "{$export} {$exportOptions}";
            exec("echo \"{$exportLine}\" | sudo tee -a ".NFSAwareInterface::EXPORTS, $output, $returnCode);
            if (0 != $returnCode) {
                throw new RuntimeException('Writing in '.NFSAwareInterface::EXPORTS.' failed.');
            }
            $io->success(NFSAwareInterface::EXPORTS.' updated.');
            if (!$this->restartNFSD($io)) {
                return false;
            }
        }

        return true;
    }

    public function standardNFSConfigurationMessage(SymfonyStyle $io, $exportLine): void
    {
        $io->caution('You are on Mac OS X, for optimal performance we recommend to mount the host through NFS.');
        $io->comment(
            "
This wizard is going to check and to do this step if required:
- Add <comment>{$exportLine}</comment> in <comment>".NFSAwareInterface::EXPORTS.'</comment>
- Add <comment>nfs.server.mount.require_resv_port = 0</comment> in <comment>'.NFSAwareInterface::RESV.'</comment>
- Add restart your nfsd server: <comment>nfsd restart</comment>
'
        );
    }
}
