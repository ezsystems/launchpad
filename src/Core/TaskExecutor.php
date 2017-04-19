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
     * @param $initialData
     */
    public function eZInstall($version, $repository, $initialData)
    {
        $recipe = 'ez_install';
        $this->checkRecipeAvailability($recipe);
        $this->execute("{$recipe}.bash {$repository} {$version} {$initialData}");
    }

    public function eZInstallSolr()
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);
        $this->execute(
            "{$recipe}.bash {$this->projectConfiguration->get('provisioning.folder_name')} COMPOSER_INSTALL"
        );
    }

    public function indexSolr()
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);
        $this->execute(
            "{$recipe}.bash {$this->projectConfiguration->get('provisioning.folder_name')} INDEX"
        );
    }

    public function createCore()
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);
        $this->execute(
            "{$recipe}.bash {$this->projectConfiguration->get('provisioning.folder_name')} CREATE_CORE",
            'solr',
            'solr'
        );
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
     * @param $arguments
     */
    public function runSymfomyCommand($arguments)
    {
        $this->execute('ezplatform/app/console '.$arguments);
    }

    /**
     * @param $arguments
     */
    public function runComposerCommand($arguments)
    {
        $this->execute('ezplatform/composer.phar --working-dir=/var/www/html/project/ezplatform '.$arguments);
    }

    /**
     * @param      $command
     * @param null $user
     * @param null $service
     */
    protected function execute($command, $user = 'www-data', $service = 'engine')
    {
        $command = '/var/www/html/project/'.$command;
        $this->dockerClient->exec($command, ['--user', $user], $service);
    }
}
