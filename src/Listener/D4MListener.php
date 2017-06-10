<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Listener;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class D4MListener.
 */
class D4MListener
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
     * @var array
     */
    protected $parameters;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * ApplicationUpdate constructor.
     *
     * @param $configuration
     */
    public function __construct($parameters, ProjectConfiguration $configuration)
    {
        $this->parameters           = $parameters;
        $this->projectConfiguration = $configuration;
    }

    /**
     * @param SymfonyStyle $io
     *
     * @return bool
     */
    protected function restartNFSD(SymfonyStyle $io)
    {
        exec('sudo nfsd restart', $output, $returnCode);
        if ($returnCode != 0) {
            $io->error('NFSD restart failed.');

            return false;
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
     * @param SymfonyStyle $io
     *
     * @return bool
     */
    protected function checkAndInstall(SymfonyStyle $io)
    {
        if (!$this->isDockerInstalled()) {
            $io->error('You need to install Docker for Mac before to run that command.');

            return false;
        }

        $isD4MScreenExist          = $this->isD4MScreenExist();
        list($export, $mountPoint) = $this->getHostMapping();
        $isResvReady               = $this->isResvReady();
        $isExportReady             = $this->isExportReady($export);
        $exportLine                = "{$export} -mapall=".getenv('USER').':staff localhost';

        if (!$isResvReady || !$isExportReady || !$isD4MScreenExist) {
            $io->caution('You are on Mac OS X, for optimal performance we recommend to mount the host through NFS.');
            $io->comment(
                "
This wizard is going to:
- Add <comment>{$exportLine}</comment> in <comment>".static::EXPORTS.'</comment>
- Add <comment>nfs.server.mount.require_resv_port = 0</comment> in <comment>'.static::RESV."</comment>
- Add restart your nfsd server: <comment>nfsd restart</comment>
- Communicate with the Docker Moby VM and add the mount point
    <comment>{$export}</comment> -> <comment>{$mountPoint}</comment>
            "
            );
            if (!$io->confirm('Do you want to setup your Mac as an NFS export?')) {
                return true;
            }
        }

        if (!$isResvReady) {
            exec('echo "nfs.server.mount.require_resv_port = 0" | sudo tee -a '.static::RESV, $output, $returnCode);
            if ($returnCode != 0) {
                $io->error('Writing in '.static::RESV.' failed.');

                return false;
            }
            $io->success(static::RESV.' updated.');
            if (!$this->restartNFSD($io)) {
                return false;
            }
        }

        if (!$isExportReady) {
            exec("echo \"{$exportLine}\" | sudo tee -a ".static::EXPORTS, $output, $returnCode);
            if ($returnCode != 0) {
                $io->error('Writing in '.static::EXPORTS.' failed.');

                return false;
            }
            $io->success(static::EXPORTS.' updated.');
            if (!$this->restartNFSD($io)) {
                return false;
            }
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

            if (!$this->isD4MScreenExist()) {
                $io->error(static::SCREEN_NAME.'screen failed to initiate. Mount point will not be ready.');

                return false;
            }

            //@todo: test the real mount point
            // screen -r d4m
            // mount
            // we should have something like:
            //192.168.65.1:/Users/plopix on /Users/plopix/ type nfs (rw,relatime,vers=3,rsize=65536,wsize=65536,namlen=255,hard,nolock,proto=tcp,timeo=600,retrans=2,sec=sys,mountaddr=192.168.65.1,mountvers=3,mountproto=tcp,local_lock=all,addr=192.168.65.1)
            // and not just /Users/plopix with osfs

            $io->success('Moby VM mount succeed.');
        }

        return true;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommandAction(ConsoleCommandEvent $event)
    {
        if (php_uname('s') != 'Darwin') {
            return;
        }
        $command = $event->getCommand();
        // don't bother for those command
        if (in_array($command->getName(), ['self-update', 'rollback', 'list', 'help', 'test'])) {
            return;
        }
        $success = $this->checkAndInstall(new SymfonyStyle($event->getInput(), $event->getOutput()));

        if (!$success) {
            $event->disableCommand();
        }

        return;
    }

    /**
     * @return bool
     */
    protected function isD4MScreenExist()
    {
        exec('screen -list | grep -q "'.static::SCREEN_NAME.'";', $output, $return);

        return $return == 0;
    }

    /**
     * @return bool
     */
    protected function isDockerInstalled()
    {
        exec('which -s docker', $output, $return);

        return $return == 0;
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

                return preg_match("#^{$export}#", $line) == 1;
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
                        return (string) explode('=', $line)[1] == '0';
                    }
                }

                return false;
            }
        );
    }
}
