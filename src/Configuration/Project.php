<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Configuration;

use eZ\Launchpad\Core\DockerCompose;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Project.
 */
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

    /**
     * Project constructor.
     *
     * @param string $globalFilePath
     * @param string $localFilePath
     * @param array  $configurations
     */
    public function __construct($globalFilePath, $localFilePath, $configurations)
    {
        $this->globalFilePath = $globalFilePath;
        $this->localFilePath  = $localFilePath;
        $this->configurations = $configurations;
    }

    /**
     * @param string $name
     *
     * @return array|mixed|null
     */
    public function get($name)
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

        if (isset($this->configurations[$name])) {
            return $this->configurations[$name];
        }

        return null;
    }

    /**
     * @param string $name
     */
    public function setLocal($name, $value)
    {
        $this->set([$name => $value], 'local');
    }

    /**
     * @param string $name
     */
    public function setGlobal($name, $value)
    {
        $this->set([$name => $value], 'global');
    }

    /**
     * @param $keyValues
     */
    public function setMultiLocal($keyValues)
    {
        $this->set($keyValues, 'local');
    }

    /**
     * @param $keyValues
     */
    public function setMultiGlobal($keyValues)
    {
        $this->set($keyValues, 'global');
    }

    /**
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Store inMemory and in the good file.
     *
     * @param array  $keyValues
     * @param string $where
     */
    protected function set($keyValues, $where = 'global')
    {
        $filePath = 'global' === $where ? $this->globalFilePath : $this->localFilePath;

        $fs     = new Filesystem();
        $config = $fs->exists($filePath) ? Yaml::parse(file_get_contents($filePath)) : [];

        foreach ($keyValues as $name => $value) {
            if (strpos($name, '.')) {
                $parts  = explode('.', $name);
                $onFile = &$config;
                foreach ($parts as $part) {
                    $onFile =&$onFile[$part];
                }
                $onFile = $value;

                $inMemory = &$this->configurations;

                foreach ($parts as $part) {
                    $inMemory =&$inMemory[$part];
                }
                $inMemory = $value;
            } else {
                $this->configurations[$name] = $value;
                $config[$name]               = $value;
            }
        }

        $yaml = Yaml::dump($config, 4);
        $fs->dumpFile($filePath, $yaml);
    }

    /**
     * @return DockerCompose
     */
    public function getDockerCompose()
    {
        $projectPath = dirname($this->localFilePath);

        return new DockerCompose(
            "{$projectPath}/"."{$this->get('provisioning.folder_name')}/".
            "{$this->environment}/{$this->get('docker.compose_filename')}"
        );
    }
}
