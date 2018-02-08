<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Core\Client\Docker;

/**
 * Trait DockerSyncCommandTrait.
 */
trait DockerSyncCommandTrait
{
    /**
     * @var bool
     */
    private $hasToEnabledDockerSync = false;

    /**
     * @param Docker $dockerClient
     */
    public function dockerSyncClientConnect(Docker $dockerClient)
    {
        if (true === $this->hasToEnabledDockerSync) {
            $dockerClient->enabledDockerSyncClient();
        }
    }

    /**
     * Flag initialization of the Docker Sync Client.
     */
    public function enabledDockerSyncClient()
    {
        $this->hasToEnabledDockerSync = true;
    }
}
