<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Core\Command;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class D4MListener.
 */
class D4M extends Optimizer implements OptimizerInterface
{
    /**
     * File to set the NFS exports.
     */
    const EXPORTS = '/etc/exports';

    /**
     * File to set the nfs.server.mount.require_resv_port = 0.
     */
    const RESV = '/etc/nfs.conf';

    const SCREEN_NAME = 'd4m';

    /**
     * @param SymfonyStyle $io
     *
     * @return bool
     */
    protected function restartNFSD(SymfonyStyle $io)
    {
        exec('sudo nfsd restart', $output, $returnCode);
        if (0 != $returnCode) {
            throw new RuntimeException('NFSD restart failed.');
        }
        $io->success('NFSD restarted.');

        return true;
    }

    /**
     * @return array
     */
    protected function getHostMapping()
    {
        $default = [getenv('HOME'), getenv('HOME')];

        $currentMapping = $this->projectConfiguration->get('docker.host_machine_mapping');
        if ($currentMapping) {
            return explode(':', $currentMapping);
        }
        $this->projectConfiguration->setGlobal('docker.host_machine_mapping', implode(':', $default));

        return $default;
    }

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
        $exportLine                = "{$export} -mapall=".getenv('USER').':staff localhost';

        $io->caution('You are on Mac OS X, for optimal performance we recommend to mount the host through NFS.');
        $io->comment(
            "
This wizard is going to check and to do this step if required:
- Add <comment>{$exportLine}</comment> in <comment>".static::EXPORTS.'</comment>
- Add <comment>nfs.server.mount.require_resv_port = 0</comment> in <comment>'.static::RESV."</comment>
- Add restart your nfsd server: <comment>nfsd restart</comment>
- Communicate with the Docker Moby VM and add the mount point
    <comment>{$export}</comment> -> <comment>{$mountPoint}</comment>
            "
        );

        return $io->confirm('Do you want to setup your Mac as an NFS export?');
    }

    /**
     * {@inheritdoc}
     */
    public function optimize(SymfonyStyle $io, Command $command)
    {
        $isD4MScreenExist          = self::isD4MScreenExist();
        list($export, $mountPoint) = $this->getHostMapping();
        $isResvReady               = $this->isResvReady();
        $isExportReady             = $this->isExportReady($export);
        $exportLine                = "{$export} -mapall=".getenv('USER').':staff localhost';

        if (!$isResvReady) {
            exec('echo "nfs.server.mount.require_resv_port = 0" | sudo tee -a '.static::RESV, $output, $returnCode);
            if (0 != $returnCode) {
                throw new RuntimeException('Writing in '.static::RESV.' failed.');
            }
            $io->success(static::RESV.' updated.');
            if (!$this->restartNFSD($io)) {
                return false;
            }
        }

        if (!$isExportReady) {
            exec("echo \"{$exportLine}\" | sudo tee -a ".static::EXPORTS, $output, $returnCode);
            if (0 != $returnCode) {
                throw new RuntimeException('Writing in '.static::EXPORTS.' failed.');
            }
            $io->success(static::EXPORTS.' updated.');
            $this->restartNFSD($io);
        }

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
     * @return bool
     */
    protected function isDockerInstalled()
    {
        exec('which -s docker', $output, $return);

        return 0 === $return;
    }

    /**
     * @return bool
     */
    protected function isExportReady($export)
    {
        $fs = new Filesystem();
        if (!$fs->exists(static::EXPORTS)) {
            return false;
        }

        return NovaCollection(file(static::EXPORTS))->exists(
            function ($line) use ($export) {
                $export = addcslashes($export, '/.');
                $line   = trim($line);

                return 1 === preg_match("#^{$export}#", $line);
            }
        );
    }

    /**
     * @return bool
     */
    protected function isResvReady()
    {
        $fs = new Filesystem();
        if (!$fs->exists(static::RESV)) {
            return false;
        }

        return NovaCollection(file(static::RESV))->exists(
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

    /**
     * {@inheritdoc}
     */
    public function init(Command $command)
    {
        //nothing to do here.
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($version)
    {
        return $version < 1712;
    }
}
