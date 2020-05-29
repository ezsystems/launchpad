<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Core\Client\Docker as DockerClient;
use Novactive\Collection\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;

class TaskExecutor
{
    /**
     * @var DockerClient
     */
    protected $dockerClient;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * @var Collection
     */
    protected $recipes;

    /**
     * @var array Docker environment variables
     */
    protected $dockerEnvVars;

    public function __construct(DockerClient $dockerClient, ProjectConfiguration $configuration, Collection $recipes, array $dockerEnvVars = [])
    {
        $this->dockerClient = $dockerClient;
        $this->projectConfiguration = $configuration;
        $this->recipes = $recipes;
        $this->dockerEnvVars = $dockerEnvVars;
    }

    protected function checkRecipeAvailability(string $recipe): void
    {
        if (!$this->recipes->contains($recipe)) {
            throw new RuntimeException("Recipe {$recipe} is not available.");
        }
    }

    /**
     * @return Process[]
     */
    public function composerInstall(): array
    {
        $recipe = 'composer_install';
        $this->checkRecipeAvailability($recipe);

        $processes = [];
        // composer install
        $processes[] = $this->execute("{$recipe}.bash");

        // Composer Configuration
        $httpBasics = $this->projectConfiguration->get('composer.http_basic');
        if (\is_array($httpBasics)) {
            foreach ($httpBasics as $auth) {
                if (!isset($auth['host'], $auth['login'], $auth['password'])) {
                    continue;
                }
                $processes[] = $this->globalExecute(
                    '/usr/local/bin/composer config --global'.
                    " http-basic.{$auth['host']} {$auth['login']} {$auth['password']}"
                );
            }
        }

        $tokens = $this->projectConfiguration->get('composer.token');
        if (\is_array($tokens)) {
            foreach ($tokens as $auth) {
                if (!isset($auth['host'], $auth['value'])) {
                    continue;
                }
                $processes[] = $this->globalExecute(
                    '/usr/local/bin/composer config --global'." github-oauth.{$auth['host']} {$auth['value']}"
                );
            }
        }

        return $processes;
    }

    public function eZInstall(string $version, string $repository, string $initialData): Process
    {
        $recipe = 'ez_install';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash {$repository} {$version} {$initialData}");
    }

    public function eZInstallSolr(): Process
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);

        return $this->execute(
            "{$recipe}.bash {$this->projectConfiguration->get('provisioning.folder_name')} COMPOSER_INSTALL"
        );
    }

    public function indexSolr(): Process
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);

        return $this->execute(
            "{$recipe}.bash {$this->projectConfiguration->get('provisioning.folder_name')} INDEX"
        );
    }

    public function createCore(): Process
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);

        $provisioningFolder = $this->projectConfiguration->get('provisioning.folder_name');

        return $this->execute(
            "{$recipe}.bash {$provisioningFolder} CREATE_CORE",
            'solr',
            'solr'
        );
    }

    public function eZCreate(): Process
    {
        $recipe = 'ez_create';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash");
    }

    public function dumpData(): Process
    {
        $recipe = 'create_dump';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash");
    }

    public function importData(): Process
    {
        $recipe = 'import_dump';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash");
    }

    public function runSymfomyCommand(string $arguments): Process
    {
        $consolePath = $this->dockerClient->isEzPlatform2x() ? 'bin/console' : 'app/console';

        return $this->execute("ezplatform/{$consolePath} {$arguments}");
    }

    public function runComposerCommand(string $arguments): Process
    {
        return $this->globalExecute(
            '/usr/local/bin/composer --working-dir='.$this->dockerClient->getProjectPathContainer().'/ezplatform '.
            $arguments
        );
    }

    protected function execute(string $command, string $user = 'www-data', string $service = 'engine')
    {
        $command = $this->dockerClient->getProjectPathContainer().'/'.$command;

        return $this->globalExecute($command, $user, $service);
    }

    protected function globalExecute(string $command, string $user = 'www-data', string $service = 'engine')
    {
        $args = ['--user', $user];

        foreach ($this->dockerEnvVars as $envVar) {
            $args = array_merge($args, ['--env', $envVar]);
        }

        return $this->dockerClient->exec($command, $args, $service);
    }

}
