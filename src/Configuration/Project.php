<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Configuration;

use eZ\Launchpad\Core\DockerCompose;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Project
{
    /**
     * @var string
     */
    protected $globalFilePath;

    /**
     * @var string
     */
    protected $localFilePath;

    /**
     * @var array
     */
    protected $configurations;

    /**
     * @var string The Current Docker Environment
     */
    protected $environment;

    public function __construct(string $globalFilePath, string $localFilePath, array $configurations)
    {
        $this->globalFilePath = $globalFilePath;
        $this->localFilePath = $localFilePath;
        $this->configurations = $configurations;
    }

    /**
     * @return array|mixed|null
     */
    public function get(string $name)
    {
        if (strpos($name, '.')) {
            $parts = explode('.', $name);
            $array = $this->configurations;
            foreach ($parts as $part) {
                if (!isset($array[$part])) {
                    return null;
                }
                $array = $array[$part];
            }

            return $array;
        }

        return $this->configurations[$name] ?? null;
    }

    public function setLocal(string $name, $value): void
    {
        $this->set([$name => $value], 'local');
    }

    public function setGlobal(string $name, $value): void
    {
        $this->set([$name => $value], 'global');
    }

    public function setMultiLocal(array $keyValues): void
    {
        $this->set($keyValues, 'local');
    }

    public function setMultiGlobal(array $keyValues): void
    {
        $this->set($keyValues, 'global');
    }

    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    /**
     * Store inMemory and in the good file.
     */
    protected function set(array $keyValues, string $where = 'global'): void
    {
        $filePath = 'global' === $where ? $this->globalFilePath : $this->localFilePath;

        $fs = new Filesystem();
        $config = $fs->exists($filePath) ? Yaml::parse(file_get_contents($filePath)) : [];

        foreach ($keyValues as $name => $value) {
            if (strpos($name, '.')) {
                $parts = explode('.', $name);
                $onFile = &$config;
                foreach ($parts as $part) {
                    $onFile = &$onFile[$part];
                }
                $onFile = $value;

                $inMemory = &$this->configurations;

                foreach ($parts as $part) {
                    $inMemory = &$inMemory[$part];
                }
                $inMemory = $value;
            } else {
                $this->configurations[$name] = $value;
                $config[$name] = $value;
            }
        }

        $yaml = Yaml::dump($config, 4);
        $fs->dumpFile($filePath, $yaml);
    }

    public function getDockerCompose(): DockerCompose
    {
        $projectPath = \dirname($this->localFilePath);

        return new DockerCompose(
            "{$projectPath}/"."{$this->get('provisioning.folder_name')}/".
            "{$this->environment}/{$this->get('docker.compose_filename')}"
        );
    }
}
