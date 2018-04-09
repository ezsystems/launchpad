<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Core\Command;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class D4MListener.
 */
class D4M extends Optimizer implements OptimizerInterface, NFSAwareInterface
{
    use NFSTrait;

    const SCREEN_NAME = 'd4m';

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        list($export, $mountPoint) = $this->getHostMapping();

        return $this->isResvReady() && $this->isExportReady($export) && self::isD4MScreenExist();
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission(SymfonyStyle $io)
    {
        list($export, $mountPoint) = $this->getHostMapping();
        $exportLine                = "{$export} {$this->getExportOptions()}";
        $this->standardNFSConfigurationMessage($io, $exportLine);
        $io->comment(
            "
- Communicate with the Docker Moby VM and add the mount point
    <comment>{$export}</comment> -> <comment>{$mountPoint}</comment>
            "
        );

        return $io->confirm('Do you want to setup your Mac as an NFS server and wire the MobbyVM through a screen?');
    }

    /**
     * @return string
     */
    public function getExportOptions()
    {
        return '-mapall='.getenv('USER').':staff localhost';
    }

    /**
     * {@inheritdoc}
     */
    public function optimize(SymfonyStyle $io, Command $command)
    {
        $isD4MScreenExist = self::isD4MScreenExist();
        $this->setupNFS($io, $this->getExportOptions());
        list($export, $mountPoint) = $this->getHostMapping();

        if (!$isD4MScreenExist) {
            $screenInit   = 'screen -AmdS '.static::SCREEN_NAME;
            $screen       = 'screen -S '.static::SCREEN_NAME.' -p 0 -X stuff';
            $mountOptions = 'nolock,local_lock=all';
            $mount        = "mount -o {$mountOptions} \\$(route|awk '/default/ {print \\$2}'):{$export} {$mountPoint}";
            $mobyTTY      = '~/Library/Containers/com.docker.docker/Data/com.docker.driver.amd64-linux/tty';

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
            $cmd      = implode(' && ', $commands);
            exec($screen.' "'.$cmd.PHP_EOL.'"');
            sleep(5);

            if (!self::isD4MScreenExist()) {
                throw new RuntimeException(
                    static::SCREEN_NAME.'screen failed to initiate. Mount point will not be ready.'
                );
            }
            $io->success('Moby VM mount succeed.');
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function isD4MScreenExist()
    {
        exec('screen -list | grep -q "'.static::SCREEN_NAME.'";', $output, $return);

        return 0 === $return;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($version)
    {
        return $version < 1712;
    }
}
