<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Core\Client\Docker as DockerClient;
use Novactive\Collection\Collection;
use Symfony\Component\Process\Process;

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
     * @return Process[]
     */
    public function composerInstall()
    {
        $recipe = 'composer_install';
        $this->checkRecipeAvailability($recipe);

        $processes = [];
        // composer install
        $processes[] = $this->execute("{$recipe}.bash");

        // Composer Configuration
        $httpBasics = $this->projectConfiguration->get('composer.http_basic');
        if (is_array($httpBasics)) {
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
        if (is_array($tokens)) {
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

    /**
     * @param $version
     * @param $repository
     * @param $initialData
     *
     * @return Process
     */
    public function eZInstall($version, $repository, $initialData)
    {
        $recipe = 'ez_install';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash {$repository} {$version} {$initialData}");
    }

    /**
     * @return Process
     */
    public function eZInstallSolr()
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);

        return $this->execute(
            "{$recipe}.bash {$this->projectConfiguration->get('provisioning.folder_name')} COMPOSER_INSTALL"
        );
    }

    /**
     * @return Process
     */
    public function indexSolr()
    {
        $recipe = 'ez_install_solr';
        $this->checkRecipeAvailability($recipe);

        return $this->execute(
            "{$recipe}.bash {$this->projectConfiguration->get('provisioning.folder_name')} INDEX"
        );
    }

    /**
     * @return Process
     */
    public function createCore()
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

    /**
     * @return Process
     */
    public function eZCreate()
    {
        $recipe = 'ez_create';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash");
    }

    /**
     * @return Process
     */
    public function dumpData()
    {
        $recipe = 'create_dump';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash");
    }

    /**
     * @return Process
     */
    public function importData()
    {
        $recipe = 'import_dump';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash");
    }

    /**
     * @param $arguments
     *
     * @return Process
     */
    public function runSymfomyCommand($arguments)
    {
        $consolePath = $this->dockerClient->isEzPlatform2x() ? 'bin/console' : 'app/console';

        return $this->execute("ezplatform/{$consolePath} {$arguments}");
    }

    /**
     * @param $arguments
     *
     * @return Process
     */
    public function runComposerCommand($arguments)
    {
        return $this->globalExecute(
            '/usr/local/bin/composer --working-dir='.$this->dockerClient->getProjectPathContainer().'/ezplatform '.
            $arguments
        );
    }

    /**
     * @param string $command
     * @param string $user
     * @param string $service
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function execute($command, $user = 'www-data', $service = 'engine')
    {
        $command = $this->dockerClient->getProjectPathContainer().'/'.$command;

        return $this->globalExecute($command, $user, $service);
    }

    /**
     * @param string $command
     * @param string $user
     * @param string $service
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function globalExecute($command, $user = 'www-data', $service = 'engine')
    {
        return $this->dockerClient->exec($command, ['--user', $user], $service);
    }
}
