<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Tests\Unit;

use eZ\Launchpad\Core\DockerCompose;

class DockerComposeTest extends TestCase
{

    /**
     * @var DockerCompose
     */
    protected $compose;

    /**
     * set up test environmemt
     */
    public function setUp()
    {
        parent::setUp();
        $this->compose = new DockerCompose(__DIR__."/../../../payload/dev/docker-compose.yml");
    }

    public function testFiltering()
    {
        $this->compose->filterServices(['engine', 'db']);
        $this->assertCount(2, $this->compose->getServices());
    }

    public function testCleanupVolumesInitialize()
    {
        $this->compose->cleanForInitialize();
        foreach ($this->compose->getServices() as $service) {
            if (!isset($service['volumes'])) {
                continue;
            }
            foreach ($service['volumes'] as $volumes) {
                $this->assertNotRegExp(
                    "/ezplatform/",
                    $volumes,
                    'It should not be any ezplatform mount for initialize.'
                );
            }
        }
    }

    public function testCleanupEnvsInitialize()
    {
        $this->compose->cleanForInitialize();
        foreach ($this->compose->getServices() as $name => $service) {
            if (!isset($service['environment'])) {
                continue;
            }
            foreach ($service['environment'] as $env) {
                $this->assertNotRegExp(
                    "/(CUSTOM_CACHE_POOL|CACHE_HOST|CACHE_REDIS_PORT|SEARCH_ENGINE|SOLR_DSN)/",
                    $env
                );
            }
        }
    }

    public function getCleanEnvVarsData()
    {
        return [
            [
                ['engine', 'redis'],
                ['SEARCH_ENGINE', 'SOLR_DSN']
            ],
            [
                ['engine', 'solr'],
                ['CACHE_HOST', 'CACHE_REDIS_PORT', 'CACHE_REDIS_PORT']
            ],
            [
                ['engine'],
                ['CACHE_HOST', 'CACHE_REDIS_PORT', 'CACHE_REDIS_PORT', 'SEARCH_ENGINE', 'SOLR_DSN']
            ]
        ];
    }

    /**
     * @dataProvider getCleanEnvVarsData
     */
    public function testCleanEnvVars($services, $toCheckVars)
    {
        $this->compose->filterServices($services);
        $this->compose->removeUselessEnvironmentsVariables();

        $environments = $this->compose->getServices()['engine']['environment'];
        $vars         = [];
        
        foreach ($environments as $env) {
            if (!strpos($env, '=')) {
                continue;
            }
            list($key, $values) = explode("=", $env);

            $vars[] = $key;
        }
        foreach ($toCheckVars as $var) {
            $this->assertNotContains($var, $vars);
        }
    }
}
