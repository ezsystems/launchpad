<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\Client;

use eZ\Launchpad\Core\ProcessRunner;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

/**
 * Class DockerSync.
 */
class DockerSync
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var ProcessRunner
     */
    protected $runner;

    /**
     * @var string
     */
    protected $path;

    /**
     * Docker constructor.
     *
     * @param array         $options
     * @param ProcessRunner $runner
     */
    public function __construct($options, ProcessRunner $runner)
    {
        $resolver = new OptionsResolver();
        $defaults = [
            'compose-file'             => null,
            'network-name'             => null,
            'provisioning-folder-name' => null,
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));
        $resolver->setAllowedTypes('compose-file', 'string');
        $resolver->setAllowedTypes('network-name', 'string');
        $resolver->setAllowedTypes('provisioning-folder-name', 'string');
        $this->options = $resolver->resolve($options);

        $this->runner = $runner;
    }

    /**
     * @return bool
     */
    public static function isOn()
    {
        $fs      = new Filesystem();
        $pidFile = getcwd().'/.docker-sync/daemon.pid';
        if (!$fs->exists($pidFile)) {
            return false;
        }

        return false !== posix_getpgid((int) file_get_contents($pidFile));
    }

    /**
     * @return string
     */
    protected function getComposeFileName()
    {
        return $this->options['compose-file'];
    }

    /**
     * @return string
     */
    protected function getNetworkName()
    {
        return $this->options['network-name'];
    }

    /**
     * @return string
     */
    protected function getProvisioningFolderName()
    {
        return $this->options['provisioning-folder-name'];
    }

    /**
     * @return Process
     */
    public function start()
    {
        return $this->perform('start');
    }

    /**
     * @return Process
     */
    public function stop()
    {
        return $this->perform('stop');
    }

    /**
     * @return Process
     */
    public function sync()
    {
        return $this->perform('sync');
    }

    /**
     * @return Process
     */
    public function clean()
    {
        return $this->perform('clean');
    }

    /**
     * @return array
     */
    public function getEnvVariables()
    {
        return
            [
                'PROJECTNETWORKNAME'     => $this->getNetworkName(),
                'PROVISIONINGFOLDERNAME' => $this->getProvisioningFolderName(),
                'COMPOSEFILE'            => $this->getComposeFileName(),
                'HOME'                   => getenv('HOME'),
                'PATH'                    => getenv('PATH')
            ];
    }

    /**
     * @param       $action
     * @param array $args
     * @param bool  $dryRun
     *
     * @return Process|string
     */
    protected function perform($action, array $args = [], $dryRun = false)
    {
        $args[]     = "--config={$this->getProvisioningFolderName()}/dev/docker-sync.yml";
        $stringArgs = implode(' ', $args);

        if (null === $this->path) {
            exec('which docker-sync', $output);
            $this->path = $output[0];
        }
        $fullCommand = trim("{$this->path} {$action} {$stringArgs}");

        if (false === $dryRun) {
            return $this->runner->run($fullCommand, $this->getEnvVariables());
        }

        return $fullCommand;
    }

    /**
     * @return string
     */
    public function getSyncCommand()
    {
        $vars = NovaCollection($this->getEnvVariables());

        $prefix = $vars->map(
            function ($value, $key) {
                return $key.'='.$value;
            }
        )->implode(' ');

        return "{$prefix} ".$this->perform('', [], true);
    }
}
