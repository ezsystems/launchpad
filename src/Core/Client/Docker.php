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
 * Class Docker.
 */
class Docker
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
     * @var DockerSync
     */
    protected $syncClient;

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
            'network-prefix-port'      => null,
            'project-path'             => null,
            'project-path-container'   => '/var/www/html/project',
            'host-machine-mapping'     => null,
            'provisioning-folder-name' => null,
            'composer-cache-dir'       => null,
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));
        $resolver->setAllowedTypes('compose-file', 'string');
        $resolver->setAllowedTypes('project-path', 'string');
        $resolver->setAllowedTypes('project-path-container', 'string');
        $resolver->setAllowedTypes('network-name', 'string');
        $resolver->setAllowedTypes('composer-cache-dir', ['null', 'string']);
        $resolver->setAllowedTypes('provisioning-folder-name', 'string');
        $resolver->setAllowedTypes('network-prefix-port', 'int');
        $resolver->setAllowedTypes('host-machine-mapping', ['null', 'string']);
        $this->options = $resolver->resolve($options);
        $this->runner  = $runner;
    }

    /**
     * Enabled the Docker Sync Client.
     */
    public function enabledDockerSyncClient()
    {
        if (EZ_ON_OSX) {
            $this->syncClient = new DockerSync(
                [
                    'compose-file'             => $this->options['compose-file'],
                    'network-name'             => $this->options['network-name'],
                    'provisioning-folder-name' => $this->options['provisioning-folder-name'],
                ],
                $this->runner
            );
        }
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
     * @return bool
     */
    public function isEzPlatform2x()
    {
        $fs = new Filesystem();

        return $fs->exists("{$this->getProjectPath()}/ezplatform/bin/console");
    }

    /**
     * @return string
     */
    public function getProjectPathContainer()
    {
        return $this->options['project-path-container'];
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

    /**
     * @return DockerSync
     */
    public function getSyncClient()
    {
        return $this->syncClient;
    }

    /**
     * @return bool
     */
    public function hasSyncCient()
    {
        return null !== $this->syncClient;
    }

    /**
     * @param string $service
     *
     * @return Process
     */
    public function start($service = '')
    {
        return $this->perform('start', $service);
    }

    /**
     * @param array  $args
     * @param string $service
     *
     * @return Process
     */
    public function build(array $args = [], $service = '')
    {
        return $this->perform('build', $service, $args);
    }

    /**
     * @param array  $args
     * @param string $service
     *
     * @return Process
     */
    public function up(array $args = [], $service = '')
    {
        return $this->perform('up', $service, $args);
    }

    /**
     * @param array  $args
     * @param string $service
     *
     * @return Process
     */
    public function remove(array $args = [], $service = '')
    {
        return $this->perform('rm', $service, $args);
    }

    /**
     * @param string $service
     *
     * @return Process
     */
    public function stop($service = '')
    {
        $result = $this->perform('stop', $service);
        if ($this->hasSyncCient()) {
            $this->syncClient->stop();
        }

        return $result;
    }

    /**
     * @param array $args
     *
     * @return Process
     */
    public function down(array $args = [])
    {
        $result = $this->perform('down', '', $args);
        if ($this->hasSyncCient()) {
            $this->syncClient->clean();
        }

        return $result;
    }

    /**
     * @param array $args
     *
     * @return Process
     */
    public function ps(array $args = [])
    {
        return $this->perform('ps', '', $args);
    }

    /**
     * @param array  $args
     * @param string $service
     *
     * @return Process
     */
    public function logs(array $args = [], $service = '')
    {
        return $this->perform('logs', $service, $args);
    }

    /**
     * @param array  $args
     * @param string $service
     *
     * @return Process
     */
    public function pull(array $args = [], $service = '')
    {
        return $this->perform('pull', $service, $args);
    }

    /**
     * @param string $command
     * @param array  $args
     * @param string $service
     *
     * @return Process
     */
    public function exec($command, array $args, $service)
    {
        $args[] = $service;
        $args[] = $command;

        return $this->perform('exec', '', $args);
    }

    /**
     * @return array
     */
    public function getComposeEnvVariables()
    {
        $projectComposePath = '../../';
        if (null != $this->options['host-machine-mapping']) {
            $projectComposePath = $this->getMachineMountPath().
                                  str_replace($this->getHostExportedPath(), '', $this->getProjectPath());
        }
        $composerCacheDir = getenv('HOME').'/.composer/cache';
        if (null != $this->options['composer-cache-dir']) {
            $composerCacheDir = $this->options['composer-cache-dir'];
        }

        return
            [
                'PROJECTNETWORKNAME'      => $this->getNetworkName(),
                'PROJECTPORTPREFIX'       => $this->getNetworkPrefixPort(),
                'PROJECTCOMPOSEPATH'      => $projectComposePath,
                'PROVISIONINGFOLDERNAME'  => $this->getProvisioningFolderName(),
                'HOST_COMPOSER_CACHE_DIR' => $composerCacheDir,
                'DEV_UID'                 => getmyuid(),
                'DEV_GID'                 => getmygid(),
                // In container composer cache directory - (will be mapped to host:composer-cache-dir)
                'COMPOSER_CACHE_DIR'      => '/var/www/composer_cache',
                // where to mount the project root directory in the container - (will be mapped to host:project-path)
                'PROJECTMAPPINGFOLDER'    => $this->getProjectPathContainer(),
                // pass the Blackfire env variable here
                'BLACKFIRE_CLIENT_ID'     => getenv('BLACKFIRE_CLIENT_ID'),
                'BLACKFIRE_CLIENT_TOKEN'  => getenv('BLACKFIRE_CLIENT_TOKEN'),
                'BLACKFIRE_SERVER_ID'     => getenv('BLACKFIRE_SERVER_ID'),
                'BLACKFIRE_SERVER_TOKEN'  => getenv('BLACKFIRE_SERVER_TOKEN'),
                // pass the DOCKER native vars for compose
                'DOCKER_HOST'             => getenv('DOCKER_HOST'),
                'DOCKER_CERT_PATH'        => getenv('DOCKER_CERT_PATH'),
                'DOCKER_TLS_VERIFY'       => getenv('DOCKER_TLS_VERIFY'),
            ];
    }

    /**
     * @param string $action
     * @param string $service
     * @param array  $args
     * @param false  $dryRun
     *
     * @return Process|string
     */
    protected function perform($action, $service = '', array $args = [], $dryRun = false)
    {
        if ($this->hasSyncCient() && !DockerSync::isOn()) {
            $this->syncClient->start();
        }

        $stringArgs = implode(' ', $args);
        $command    = "docker-compose -p {$this->getNetworkName()} -f {$this->getComposeFileName()}";
        if ($this->hasSyncCient()) {
            $osxExtension = str_replace('.yml', '-osx.yml', $this->getComposeFileName());
            $fs           = new Filesystem();
            if ($fs->exists($osxExtension)) {
                $command .= " -f {$osxExtension}";
            }
        }

        $fullCommand = trim("{$command} {$action} {$stringArgs} {$service} ");

        if (false === $dryRun) {
            return $this->runner->run($fullCommand, $this->getComposeEnvVariables());
        }

        return $fullCommand;
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

        return "{$prefix} ".$this->perform('', '', [], true);
    }
}
