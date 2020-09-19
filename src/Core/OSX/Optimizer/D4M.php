<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Core\Command;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

class D4M extends Optimizer implements OptimizerInterface, NFSAwareInterface
{
    use NFSTrait;

    public const SCREEN_NAME = 'd4m';

    public function isEnabled(): bool
    {
        list($export, $mountPoint) = $this->getHostMapping();
        MacOSPatherize($export);

        return $this->isResvReady() && $this->isExportReady($export) && self::isD4MScreenExist();
    }

    public function hasPermission(SymfonyStyle $io): bool
    {
        list($export, $mountPoint) = $this->getHostMapping();
        MacOSPatherize($export);

        $exportLine = "{$export} {$this->getExportOptions()}";

        $this->standardNFSConfigurationMessage($io, $exportLine);
        $io->comment(
            "
- Communicate with the Docker Moby VM and add the mount point
    <comment>{$export}</comment> -> <comment>{$mountPoint}</comment>
            "
        );

        return $io->confirm('Do you want to setup your Mac as an NFS server and wire the MobbyVM through a screen?');
    }

    public function getExportOptions(): string
    {
        return '-mapall='.getenv('USER').':staff localhost';
    }

    public function optimize(SymfonyStyle $io, Command $command): bool
    {
        $isD4MScreenExist = self::isD4MScreenExist();
        $this->setupNFS($io, $this->getExportOptions());
        list($export, $mountPoint) = $this->getHostMapping();
        MacOSPatherize($export);
        if (!$isD4MScreenExist) {
            $screenInit = 'screen -AmdS '.static::SCREEN_NAME;
            $screen = 'screen -S '.static::SCREEN_NAME.' -p 0 -X stuff';
            $mountOptions = 'nolock,local_lock=all';
            $mount = "mount -o {$mountOptions} \\$(route|awk '/default/ {print \\$2}'):{$export} {$mountPoint}";
            $mobyTTY = '~/Library/Containers/com.docker.docker/Data/com.docker.driver.amd64-linux/tty';

            exec("{$screenInit} {$mobyTTY}");
            exec($screen.' $(printf "root\\r\\n")');
            $commands = [
                'sleep 2',
                'apk add --update nfs-utils',
                "mkdir -p {$mountPoint}",
                'rpcbind -s',
                'sleep 2',
                $mount,
            ];
            $cmd = implode(' && ', $commands);
            exec($screen.' "'.$cmd.PHP_EOL.'"');
            sleep(5);

            if (!self::isD4MScreenExist()) {
                $message = static::SCREEN_NAME.'screen failed to initiate. Mount point will not be ready.';
                throw new RuntimeException($message);
            }
            $io->success('Moby VM mount succeed.');
        }

        return true;
    }

    public static function isD4MScreenExist(): bool
    {
        $output = $return = null;
        exec('screen -list | grep -q "'.static::SCREEN_NAME.'";', $output, $return);

        return 0 === $return;
    }

    public function supports(int $version): bool
    {
        return $version < 1712;
    }
}
