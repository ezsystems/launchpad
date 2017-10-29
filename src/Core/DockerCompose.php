<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class DockerCompose
{
    /**
     * @var array
     */
    protected $compose;

    /**
     * DockerCompose constructor.
     *
     * @param $filePath
     */
    public function __construct($filePath)
    {
        $this->compose = Yaml::parse(file_get_contents($filePath));
    }

    /**
     * @return mixed
     */
    public function getServices()
    {
        return $this->compose['services'];
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasService($name)
    {
        return isset($this->compose['services'][$name]);
    }

    /**
     * @param $destination
     */
    public function dump($destination)
    {
        $yaml = Yaml::dump($this->compose, 4);
        $fs   = new Filesystem();
        $fs->dumpFile($destination, $yaml);
    }

    /**
     * @param array $selectedServices
     */
    public function filterServices($selectedServices)
    {
        $services = [];
        foreach ($this->getServices() as $name => $service) {
            if (!in_array($name, $selectedServices)) {
                continue;
            }
            $services[$name] = $service;
        }
        $this->compose['services'] = $services;
    }

    public function cleanForInitialize()
    {
        $services = [];
        foreach ($this->getServices() as $name => $service) {
            // we don't need anything else for the init
            if (!in_array($name, ['engine', 'db'])) {
                continue;
            }

            if (isset($service['volumes'])) {
                $volumes            = NovaCollection($service['volumes']);
                $service['volumes'] = $volumes->prune(
                    function ($value) {
                        return !preg_match('/ezplatform/', $value);
                    }
                )->toArray();
            }
            if (isset($service['environment'])) {
                $environnementVars      = NovaCollection($service['environment']);
                $service['environment'] = $environnementVars->prune(
                    function ($value) {
                        return !preg_match(
                            '/(CUSTOM_CACHE_POOL|CACHE_HOST|CACHE_MEMCACHED_PORT|SEARCH_ENGINE|SOLR_DSN)/',
                            $value
                        );
                    }
                )->values()->toArray();
            }
            $services[$name] = $service;
        }
        $this->compose['services'] = $services;
    }

    public function removeUselessEnvironmentsVariables()
    {
        $services = [];
        foreach ($this->getServices() as $name => $service) {
            if (isset($service['environment'])) {
                $environnementVars      = NovaCollection($service['environment']);
                $service['environment'] = $environnementVars->prune(
                    function ($value) {
                        if (!$this->hasService('solr')) {
                            if (preg_match(
                                '/(SEARCH_ENGINE|SOLR_DSN)/',
                                $value
                            )) {
                                return false;
                            }
                        }
                        if (!$this->hasService('memcache')) {
                            if (preg_match(
                                '/(CUSTOM_CACHE_POOL|CACHE_HOST|CACHE_MEMCACHED_PORT)/',
                                $value
                            )) {
                                return false;
                            }
                        }
                        if (!$this->hasService('varnish')) {
                            if (preg_match(
                                '/(HTTPCACHE_PURGE_SERVER)/',
                                $value
                            )) {
                                return false;
                            }
                        }

                        return true;
                    }
                )->values()->toArray();
            }
            $services[$name] = $service;
        }
        $this->compose['services'] = $services;
    }
}
