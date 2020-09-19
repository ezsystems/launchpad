<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Tests\Unit;

use Exception;
use eZ\Launchpad\Configuration\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends TestCase
{

    protected static $defaultConfiguration = [
        'docker' => [
            "compose_filename" => "docker-compose.yml",
            "network_prefix_port" => "42",
            "host_machine_mapping" => null,
            "network_name" => "default-ezlaunchpad",
            "host_composer_cache_dir" => null
        ],
        "provisioning" => [
            "folder_name" => "provisioning"
        ],
        "last_update_check" => null
    ];

    protected function process($configs): array
    {
        $processor = new Processor();
        $configuration = new Configuration();

        return $processor->processConfiguration(
            $configuration,
            $configs
        );
    }

    public function testDefaultLoad(): void
    {
        $config = $this->process([]);
        $this->assertEquals(static::$defaultConfiguration, $config);
    }

    public function getYamlExamples(): array
    {
        return [
            ['', 'ok'],
            [
                '
last_update_check: 1491955697
provisioning:
    folder_name: provisio
docker:
    compose_filename: compose.yml
    network_name: something
    network_prefix_port: 43
            ', 'ok',
            ],
            [
                '
provisioning:
docker:
            ', 'default',
            ],
            [
                '
docker:
            ', 'default',
            ],
            [
                '
last_update_check: ~
            ', 'default',
            ],
            [
                '
provisioni2ng:
            ', 'exception',
            ],
        ];
    }

    /**
     * @dataProvider getYamlExamples
     */
    public function testYamlLoading($yaml, $expected): void
    {
        if ($expected === 'exception') {
            $this->expectException(Exception::class);
        }

        $configuration = Yaml::parse($yaml);
        $config = $this->process([$configuration]);

        if ($expected === 'ok') {
            $this->assertIsArray($config);
        }
        if ($expected === 'default') {
            $this->assertIsArray($config);
            $this->assertEquals(static::$defaultConfiguration, $config);
        }
    }

    /**
     * @dataProvider getYamlExamples
     */
    public function testYamlException($yaml, $expected)
    {
        $this->expectException(InvalidConfigurationException::class);
        $configuration = Yaml::parse($yaml);
        $this->process([$configuration]);
        if ($expected !== 'exception') {
            throw new InvalidConfigurationException('mock');
        }
    }
}
