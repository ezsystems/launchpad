<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Tests\Unit;

class DockerClientTest extends TestCase
{
    /**
     * @var array
     */
    protected $environmentVariables;

    /**
     * set up test environmemt
     */
    public function setUp()
    {
        parent::setUp();
        $this->environmentVariables = $this->getDockerClientEnvironmentVariables();
    }

    public function testEnvVariables(): void
    {
        $this->assertEquals($this->environmentVariables, $this->getDockerClient()->getComposeEnvVariables());
    }

    public function testGetCommand(): void
    {
        $vars = NovaCollection($this->environmentVariables);

        $prefix = $vars->map(
            function ($value, $key) {
                return $key.'='.$value;
            }
        )->implode(' ');

        $expected = "{$prefix} docker-compose -p test -f ".$this->getDockerComposeFilePath();
        $this->assertEquals($expected, $this->getDockerClient()->getComposeCommand());
    }

    public function getTestedActions(): array
    {
        return [
            ['ps', [[]], 'ps'],
            ['ps', [['-q']], 'ps -q'],
            ['ps', [['-q', '-ez']], 'ps -q -ez'],
            ['down', [[]], 'down'],
            ['down', [['-q']], 'down -q'],
            ['down', [['-q', '-ez']], 'down -q -ez'],
            ['start', ['plop'], 'start  plop'],
            ['stop', ['plop'], 'stop  plop'],

            ['logs', [['-q', '-ez'], 'plop'], 'logs -q -ez plop'],
            ['up', [['-q', '-ez'], 'plop'], 'up -q -ez plop'],
            ['build', [['-q', '-ez'], 'plop'], 'build -q -ez plop'],
            ['remove', [['-q', '-ez'], 'plop'], 'rm -q -ez plop'],

            ['exec', ['/bin/bash', ['-q', '-ez'], 'plop'], 'exec -q -ez plop /bin/bash'],
            ['exec', ['/bin/bash', [], 'plop'], 'exec plop /bin/bash'],

            // no tty
            ['exec', ['/bin/bash', ['-q', '-ez'], 'plop'], 'exec -T -q -ez plop /bin/bash', false],
            ['exec', ['/bin/bash', [], 'plop'], 'exec -T plop /bin/bash', false],
        ];
    }

    /**
     * @dataProvider getTestedActions
     */
    public function testRun(string $method, array $args, string $expectedCommandSuffix, bool $hasTty = true): void
    {
        $client = $this->getDockerClient($hasTty);
        $mockedResults = \call_user_func_array([$client, $method], $args);

        $this->assertCount(2, $mockedResults);
        $this->assertEquals($mockedResults[1], $this->environmentVariables);

        $command = "docker-compose -p test -f ".$this->getDockerComposeFilePath();
        $suffix = trim(str_replace($command, '', $mockedResults[0]));

        $this->assertEquals($expectedCommandSuffix, $suffix);
    }
}
