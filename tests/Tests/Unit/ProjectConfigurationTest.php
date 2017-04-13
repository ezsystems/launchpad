<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Tests\Unit;

use eZ\Launchpad\Configuration\Configuration;
use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Class ProjectConfigurationTest
 */
class ProjectConfigurationTest extends TestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private $root;

    /**
     * set up test environmemt
     */
    public function setUp()
    {
        $this->root          = vfsStream::setup('ezlaunchpad');
        $globalConfiguration = vfsStream::url("ezlaunchpad/ez.yml");
        $localConfiguration  = vfsStream::url("ezlaunchpad/.ezlaunchpad.yml");

        file_put_contents(
            $globalConfiguration,
            '
docker:
    host_machine_mapping: "/Users/plopix/DOCKFILES:/data/DOCKER_SOURCES"
    host_composer_cache_dir: "/data/DOCKER_SOURCES/.composer_cache"
    compose_filename: docker-compose-test.yml

provisioning:
    folder_name: "provisioning2ouf"

composer:
    http_basic:
        ez:
            host: ez.no
            login: login
            password: novactive
        plopix:
            host: plopix.net
            login: login
            password: pass
        '
        );

        file_put_contents(
            $localConfiguration,
            '
last_update_check: 1491955697
provisioning:
    folder_name: provisioning_test
docker:
    network_name: newversion_test
    network_prefix_port: 123
        '
        );
    }

    protected function process($configs)
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        return $processor->processConfiguration(
            $configuration,
            $configs
        );
    }

    protected function getConfiguration()
    {
        $globalFile = $this->root->getChild('ez.yml')->url();
        $localFile  = $this->root->getChild('.ezlaunchpad.yml')->url();

        return new ProjectConfiguration(
            $globalFile,
            $localFile,
            $this->process(
                [
                    Yaml::parse(file_get_contents($globalFile)),
                    Yaml::parse(file_get_contents($localFile)),
                ]
            )
        );
    }

    public function getKeyValuesToTest()
    {
        return [
            ['last_update_check', '1491955697'],
            ['provisioning.folder_name', 'provisioning_test'],
            ['provisioning.plop', null],
            ['docker.compose_filename', 'docker-compose-test.yml'],
            ['docker.network_name', 'newversion_test'],
            ['docker.network_prefix_port', '123'],
            ['docker.host_machine_mapping', '/Users/plopix/DOCKFILES:/data/DOCKER_SOURCES'],
            ['docker.host_composer_cache_dir', "/data/DOCKER_SOURCES/.composer_cache"],
            ['docker.host_compossdasder_cache_dir', null],
            ['composer.http_basic.plopix.host', 'plopix.net'],
            ['composer.http_basic.plopix.login', 'login'],
            ['composer.http_basic.plopix.password', 'pass'],
            ['composer.http_basic.ez.host', 'ez.no'],
            ['composer.http_basic.ez.login', 'login'],
            ['composer.http_basic.ez.password', 'novactive'],
            ['fake', null],
        ];
    }

    /**
     * @dataProvider getKeyValuesToTest
     *
     * @param $key
     * @param $expectedValue
     */
    public function testKeyValue($key, $expectedValue)
    {
        $projectConfiguration = $this->getConfiguration();
        $this->assertEquals($projectConfiguration->get($key), $expectedValue);
    }

    public function testSet($where = 'local')
    {
        $method                    = "set".ucfirst($where);
        $var                       = 'docker.compose_filename';
        $firstProjectConfiguration = $this->getConfiguration();
        $this->assertEquals($firstProjectConfiguration->get($var), 'docker-compose-test.yml');
        $firstProjectConfiguration->$method($var, 'docker-compose-test.yml_CHANGED');
        $this->assertEquals($firstProjectConfiguration->get($var), 'docker-compose-test.yml_CHANGED');
        $secondProjectConfiguration = $this->getConfiguration();
        $this->assertEquals($secondProjectConfiguration->get($var), 'docker-compose-test.yml_CHANGED');
        $this->assertEquals($firstProjectConfiguration, $secondProjectConfiguration);
    }

    public function testSetGlobal()
    {
        $this->testSet('global');

        $configuration = $this->getConfiguration();
        $configuration->setGlobal('provisioning.folder_name', 'untruc');
        // change on the fly
        $this->assertEquals($configuration->get('provisioning.folder_name'), 'untruc');

        // does not the next load
        $secondProjectConfiguration = $this->getConfiguration();
        $this->assertEquals($secondProjectConfiguration->get('provisioning.folder_name'), 'provisioning_test');
    }
}
