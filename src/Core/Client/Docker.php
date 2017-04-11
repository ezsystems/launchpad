<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\Client;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

/**
 * Class Docker.
 */
class Docker
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Docker constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $resolver = new OptionsResolver();
        $defaults = [
            'compose-file'             => null,
            'network-name'             => null,
            'network-prefix-port'      => null,
            'project-path'             => null,
            'host-machine-mapping'     => null,
            'provisioning-folder-name' => null,
            'composer-cache-dir'       => null,
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));
        $resolver->setAllowedTypes('compose-file', 'string');
        $resolver->setAllowedTypes('project-path', 'string');
        $resolver->setAllowedTypes('network-name', 'string');
        $resolver->setAllowedTypes('composer-cache-dir', ['null', 'string']);
        $resolver->setAllowedTypes('provisioning-folder-name', 'string');
        $resolver->setAllowedTypes('network-prefix-port', 'int');
        $resolver->setAllowedTypes('host-machine-mapping', ['null', 'string']);
        $this->options = $resolver->resolve($options);
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
     * @return int
     */
    protected function getNetworkPrefixPort()
    {
        return $this->options['network-prefix-port'];
    }

    /**
     * @return string
     */
    protected function getProjectPath()
    {
        return $this->options['project-path'];
    }

    /**
     * @return string
     */
    protected function getProvisioningFolderName()
    {
        return $this->options['provisioning-folder-name'];
    }

    /**
     * @return mixed
     */
    protected function getHostExportedPath()
    {
        return explode(':', $this->options['host-machine-mapping'])[0];
    }

    /**
     * @return mixed
     */
    protected function getMachineMountPath()
    {
        return explode(':', $this->options['host-machine-mapping'])[1];
    }

    public function start($service = '')
    {
        $this->perform('start', $service);
    }

    public function stop($service = '')
    {
        $this->perform('stop', $service);
    }

    public function build($args = [], $service = '')
    {
        $this->perform('build', $service, $args);
    }

    public function up($args = [], $service = '')
    {
        $this->perform('up', $service, $args);
    }

    public function remove($args = [], $service = '')
    {
        $this->perform('rm', $service, $args);
    }

    public function ps($args = [], $service = '')
    {
        $this->perform('ps', $service, $args);
    }

    public function logs($args = [], $service = '')
    {
        $this->perform('logs', $service, $args);
    }

    public function exec($command, $args, $service)
    {
        array_push($args, $service);
        array_push($args, $command);
        $this->perform('exec', '', $args);
    }

    /**
     * @return string
     */
    public function getComposeEnvVariables()
    {
        $projectComposePath = '../../';
        if ($this->options['host-machine-mapping'] != null) {
            $projectComposePath = $this->getMachineMountPath().
                                  str_replace($this->getHostExportedPath(), '', $this->getProjectPath());
        }
        $composerCacheDir = getenv('HOME').'/.composer/cache';
        if ($this->options['composer-cache-dir'] != null) {
            $composerCacheDir = $this->options['composer-cache-dir'];
        }

        return
            [
                'PROJECTPORTPREFIX'       => $this->getNetworkPrefixPort(),
                'PROJECTCOMPOSEPATH'      => $projectComposePath,
                'PROVISIONINGFOLDERNAME'  => $this->getProvisioningFolderName(),
                'HOST_COMPOSER_CACHE_DIR' => $composerCacheDir,
            ];
    }

    /**
     * @param string $action
     */
    protected function perform($action, $service = '', $args = [])
    {
        $args    = implode(' ', $args);
        $command = "docker-compose -p {$this->getNetworkName()} -f {$this->getComposeFileName()}";

        $command = "{$command} {$action} {$args} {$service} ";
        $process = new Process(escapeshellcmd($command), null, $this->getComposeEnvVariables());
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $process->setTty(true);
        }
        $process->setTimeout(2 * 3600);
        $process->mustRun();
    }

    /**
     * @return string
     */
    public function getComposeCommand()
    {
        $vars = NovaCollection($this->getComposeEnvVariables());

        $prefix = $vars->map(
            function ($value, $key) {
                return $key.'='.$value;
            }
        )->implode(' ');

        $command = "docker-compose -p {$this->getNetworkName()} -f {$this->getComposeFileName()}";

        return "{$prefix} {$command}";
    }
}
