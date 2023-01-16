<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use eZ\Launchpad\Configuration\Configuration;
use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Core\Client\Docker;
use eZ\Launchpad\Core\ProcessRunner;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private $root;

    protected function setUp(): void
    {
        $structure = array(
            'provisioning_test' => array(
                'dev' => array(
                    'docker-compose-test.yml' => "
version: '2.2'
                    ",
                ),
            ),
            'ez.yml' => '
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
            ',
            '.ezlaunchpad.yml' => '
last_update_check: 1491955697
project:
    cms_version: ibexa-4
provisioning:
    folder_name: provisioning_test
docker:
    network_name: newversion_test
    network_prefix_port: 123
            '
        );

        $this->root = vfsStream::setup('ezlaunchpad', null,$structure);
    }

    protected function process($configs): array
    {
        $processor = new Processor();
        $configuration = new Configuration();

        return $processor->processConfiguration(
            $configuration,
            $configs
        );
    }

    protected function getConfiguration(): ProjectConfiguration
    {
        $globalFile = $this->root->getChild('ez.yml')->url();
        $localFile = $this->root->getChild('.ezlaunchpad.yml')->url();

        $pConfiguration = new ProjectConfiguration(
            $globalFile,
            $localFile,
            $this->process(
                [
                    Yaml::parse(file_get_contents($globalFile)),
                    Yaml::parse(file_get_contents($localFile)),
                ]
            )
        );
        $pConfiguration->setEnvironment('dev');

        return $pConfiguration;
    }

    public function getDockerComposeFilePath(): string
    {
        return __DIR__."/../../../payload/dev/docker-compose.yml";
    }

    public function getDockerClient(bool $hasTty = true): Docker
    {
        $options = [
            'compose-file' => $this->getDockerComposeFilePath(),
            'network-name' => "test",
            'network-prefix-port' => 42,
            'project-path' => getcwd(),
            'provisioning-folder-name' => 'provisioning',
            'project-cms-path-container' => '/var/www/html/project/ibexa',
            'project-session-handler' => 'Ibexa\Bundle\Core\Session\Handler\NativeSessionHandler',
            'console-path' => '/var/www/html/project/ibexa/bin/console'
        ];

        $processRunnerMock = $this->getMockBuilder(ProcessRunner::class)->getMock();

        $processRunnerMock
            ->method('run')
            ->will(
                $this->returnCallback(
                    function () {
                        return \func_get_args();
                    }
                )
            );

        $processRunnerMock
            ->method('hasTty')
            ->willReturn($hasTty);

        return new Docker($options, $processRunnerMock);
    }

    public function getDockerClientEnvironmentVariables(): array
    {
        return [
            'PROJECTNETWORKNAME' => 'test',
            "PROJECTPORTPREFIX" => 42,
            "PROJECTCOMPOSEPATH" => "../../",
            "PROVISIONINGFOLDERNAME" => "provisioning",
            "HOST_COMPOSER_CACHE_DIR" => MacOSPatherize(getenv('HOME')."/.composer/cache"),
            "DEV_UID" => getmyuid(),
            "DEV_GID" => getmygid(),
            'COMPOSER_CACHE_DIR' => "/var/www/composer_cache",
            'PROJECTMAPPINGFOLDER' => "/var/www/html/project",
            'PROJECTCMSROOT' => '/var/www/html/project/ibexa',
            'PROJECT_SESSION_HANDLER_ID' => 'Ibexa\Bundle\Core\Session\Handler\NativeSessionHandler',
            'CONSOLE_PATH' => '/var/www/html/project/ibexa/bin/console',
            'BLACKFIRE_CLIENT_ID' => getenv('BLACKFIRE_CLIENT_ID'),
            'BLACKFIRE_CLIENT_TOKEN' => getenv('BLACKFIRE_CLIENT_TOKEN'),
            'BLACKFIRE_SERVER_ID' => getenv('BLACKFIRE_SERVER_ID'),
            'BLACKFIRE_SERVER_TOKEN' => getenv('BLACKFIRE_SERVER_TOKEN'),
            'DOCKER_HOST' => getenv('DOCKER_HOST'),
            'DOCKER_CERT_PATH' => getenv('DOCKER_CERT_PATH'),
            'DOCKER_TLS_VERIFY' => getenv('DOCKER_TLS_VERIFY'),
            'PATH' => getenv('PATH'),
            'XDEBUG_ENABLED' => getenv('XDEBUG_ENABLED') === false ? '0' : '1',
        ];
    }
}
