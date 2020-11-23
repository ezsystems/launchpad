<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core\Client;

use eZ\Launchpad\Core\OSX\Optimizer\NFSVolumes;
use eZ\Launchpad\Core\OSX\Optimizer\OptimizerInterface;
use eZ\Launchpad\Core\ProcessRunner;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

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
     * @var OptimizerInterface
     */
    protected $optimizer;

    public function __construct(array $options, ProcessRunner $runner, ?OptimizerInterface $optimizer = null)
    {
        $resolver = new OptionsResolver();
        $defaults = [
            'compose-file' => null,
            'network-name' => null,
            'network-prefix-port' => null,
            'project-path' => null,
            'project-path-container' => '/var/www/html/project',
            'host-machine-mapping' => null,
            'provisioning-folder-name' => null,
            'composer-cache-dir' => null,
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
        $this->runner = $runner;
        $this->optimizer = $optimizer;
    }

    protected function getComposeFileName(): string
    {
        return $this->options['compose-file'];
    }

    protected function getNetworkName(): string
    {
        return $this->options['network-name'];
    }

    protected function getNetworkPrefixPort(): int
    {
        return $this->options['network-prefix-port'];
    }

    protected function getProjectPath(): string
    {
        return $this->options['project-path'];
    }

    public function isEzPlatform2x(): bool
    {
        $fs = new Filesystem();

        return $fs->exists("{$this->getProjectPath()}/ezplatform/bin/console");
    }

    public function getProjectPathContainer(): string
    {
        return $this->options['project-path-container'];
    }

    protected function getProvisioningFolderName(): string
    {
        return $this->options['provisioning-folder-name'];
    }

    protected function getHostExportedPath(): string
    {
        return explode(':', $this->options['host-machine-mapping'])[0];
    }

    protected function getMachineMountPath(): string
    {
        return explode(':', $this->options['host-machine-mapping'])[1];
    }

    public function start(string $service = '')
    {
        return $this->perform('start', $service);
    }

    public function build(array $args = [], string $service = '')
    {
        return $this->perform('build', $service, $args);
    }

    public function up(array $args = [], string $service = '')
    {
        return $this->perform('up', $service, $args);
    }

    public function remove(array $args = [], string $service = '')
    {
        return $this->perform('rm', $service, $args);
    }

    public function stop(string $service = '')
    {
        return $this->perform('stop', $service);
    }

    public function down(array $args = [])
    {
        return $this->perform('down', '', $args);
    }

    public function ps(array $args = [])
    {
        return $this->perform('ps', '', $args);
    }

    public function logs(array $args = [], string $service = '')
    {
        return $this->perform('logs', $service, $args);
    }

    public function pull(array $args = [], string $service = '')
    {
        return $this->perform('pull', $service, $args);
    }

    public function exec(string $command, array $args, string $service)
    {
        $args[] = $service;
        $args[] = $command;

        // Disable TTY if is not supported by the host (CI)
        if (!$this->runner->hasTty()) {
            array_unshift($args, '-T');
        }

        return $this->perform('exec', '', $args);
    }

    public function getComposeEnvVariables(): array
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
                'PROJECTNETWORKNAME' => $this->getNetworkName(),
                'PROJECTPORTPREFIX' => $this->getNetworkPrefixPort(),
                'PROJECTCOMPOSEPATH' => MacOSPatherize($projectComposePath),
                'PROVISIONINGFOLDERNAME' => $this->getProvisioningFolderName(),
                'HOST_COMPOSER_CACHE_DIR' => MacOSPatherize($composerCacheDir),
                'DEV_UID' => getmyuid(),
                'DEV_GID' => getmygid(),
                // In container composer cache directory - (will be mapped to host:composer-cache-dir)
                'COMPOSER_CACHE_DIR' => '/var/www/composer_cache',
                // where to mount the project root directory in the container - (will be mapped to host:project-path)
                'PROJECTMAPPINGFOLDER' => $this->getProjectPathContainer(),
                // pass the Blackfire env variable here
                'BLACKFIRE_CLIENT_ID' => getenv('BLACKFIRE_CLIENT_ID'),
                'BLACKFIRE_CLIENT_TOKEN' => getenv('BLACKFIRE_CLIENT_TOKEN'),
                'BLACKFIRE_SERVER_ID' => getenv('BLACKFIRE_SERVER_ID'),
                'BLACKFIRE_SERVER_TOKEN' => getenv('BLACKFIRE_SERVER_TOKEN'),
                // pass the DOCKER native vars for compose
                'DOCKER_HOST' => getenv('DOCKER_HOST'),
                'DOCKER_CERT_PATH' => getenv('DOCKER_CERT_PATH'),
                'DOCKER_TLS_VERIFY' => getenv('DOCKER_TLS_VERIFY'),
                'PATH' => getenv('PATH'),
                'XDEBUG_ENABLED' => false === getenv('XDEBUG_ENABLED') ? '0' : '1',
            ];
    }

    /**
     * @return Process|string
     */
    protected function perform(string $action, string $service = '', array $args = [], bool $dryRun = false)
    {
        $stringArgs = implode(' ', $args);
        $command = "docker-compose -p {$this->getNetworkName()} -f {$this->getComposeFileName()}";

        if ($this->optimizer instanceof NFSVolumes) {
            $osxExtension = str_replace('.yml', '-osx.yml', $this->getComposeFileName());
            $fs = new Filesystem();
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

    public function getComposeCommand(): string
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
