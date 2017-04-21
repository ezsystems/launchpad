<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Tests\Unit;

use eZ\Launchpad\Core\Client\Docker;
use eZ\Launchpad\Core\ProcessRunner;

class DockerClientTest extends TestCase
{

    /**
     * @var Docker
     */
    protected $client;

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
        $this->client               = $this->getDockerClient();
        $this->environmentVariables = $this->getDockerClientEnvironmentVariables();
    }

    public function testEnvVariables()
    {
        $this->assertEquals($this->environmentVariables, $this->client->getComposeEnvVariables());
    }

    public function testGetCommand()
    {
        $vars = NovaCollection($this->environmentVariables);

        $prefix = $vars->map(
            function ($value, $key) {
                return $key.'='.$value;
            }
        )->implode(' ');

        $expected = "{$prefix} docker-compose -p test -f ".$this->getDockerComposeFilePath();
        $this->assertEquals($expected, $this->client->getComposeCommand());
    }

    public function getTestedActions()
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

        ];
    }

    /**
     * @dataProvider getTestedActions
     *
     * @param string $method
     * @param array  $args
     * @param string $expectedCommandSuffix
     */
    public function testRun($method, $args, $expectedCommandSuffix)
    {
        $command       = "docker-compose -p test -f ".$this->getDockerComposeFilePath();
        $mockedResults = call_user_func_array([$this->client, $method], $args);
        $this->assertCount(2, $mockedResults);
        $this->assertEquals($mockedResults[1], $this->environmentVariables);
        $suffix = trim(str_replace($command, '', $mockedResults[0]));
        $this->assertEquals($expectedCommandSuffix, $suffix);
    }
}
