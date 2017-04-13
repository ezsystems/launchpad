<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Core\Client\Docker as DockerClient;
use Novactive\Collection\Collection;

/**
 * Class TaskExecutor.
 */
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
     * @var string
     */
    protected $service;

    /**
     * @var string
     */
    protected $user;

    /**
     * Executor constructor.
     *
     * @param DockerClient         $dockerClient
     * @param ProjectConfiguration $configuration
     */
    public function __construct(DockerClient $dockerClient, ProjectConfiguration $configuration, Collection $recipes)
    {
        $this->dockerClient         = $dockerClient;
        $this->projectConfiguration = $configuration;
        $this->recipes              = $recipes;
        $this->service              = 'engine';
        $this->user                 = 'www-data';
    }

    /**
     * @param string $recipe
     */
    protected function checkRecipeAvailability($recipe)
    {
        if (!$this->recipes->contains($recipe)) {
            throw new \RuntimeException("Recipe {$recipe} is not available.");
        }
    }

    /**
     * Composer Install and Configurations (auth and token).
     */
    public function composerInstall()
    {
        $recipe = 'composer_install';
        $this->checkRecipeAvailability($recipe);

        // composer install
        $this->execute("{$recipe}.bash");

        // Composer Configuration
        foreach ($this->projectConfiguration->get('composer.http_basic') as $auth) {
            if (!isset($auth['host']) || !isset($auth['login']) || !isset($auth['password'])) {
                continue;
            }
            $this->execute(
                'composer.phar config --global'." http-basic.{$auth['host']} {$auth['login']} {$auth['password']}"
            );
        }

        //@todo install the token too
    }

    /**
     * @param $version
     * @param $repository
     */
    public function eZInstall($version, $repository)
    {
        $recipe = 'ez_install';
        $this->checkRecipeAvailability($recipe);
        $this->execute("{$recipe}.bash {$repository} {$version}");
    }

    public function eZCreate()
    {
        $recipe = 'ez_create';
        $this->checkRecipeAvailability($recipe);
        $this->execute("{$recipe}.bash");
    }

    /**
     * Dump Data.
     */
    public function dumpData()
    {
        $recipe = 'create_dump';
        $this->checkRecipeAvailability($recipe);
        $this->execute("{$recipe}.bash");
    }

    /**
     * Import Dump.
     */
    public function importData()
    {
        $recipe = 'import_dump';
        $this->checkRecipeAvailability($recipe);
        $this->execute("{$recipe}.bash");
    }

    /**
     * @todo: improve here and make the options working
     *
     * @param $arguments
     */
    public function runSymfomyCommand($arguments)
    {
        $this->execute('ezplatform/app/console '.$arguments);
    }

    /**
     * @param $command
     */
    protected function execute($command)
    {
        $command = '/var/www/html/project/'.$command;
        $this->dockerClient->exec($command, ['--user', $this->user], $this->service);
    }
}
