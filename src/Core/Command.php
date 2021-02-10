<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Core\OSX\Optimizer\OptimizerInterface;
use Novactive\Collection\Collection;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Command extends BaseCommand
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * @var string
     */
    protected $appDir;

    /**
     * @var string
     */
    protected $projectPath;

    /**
     * @var Collection
     */
    protected $requiredRecipes;

    /**
     * @var OptimizerInterface
     */
    protected $optimizer;

    /**
     * @var ProjectWizardInterface
     */
    protected $projectWizard;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function setProjectConfiguration(ProjectConfiguration $configuration): self
    {
        $this->projectConfiguration = $configuration;

        return $this;
    }

    public function setAppDir(string $appDir): void
    {
        $this->appDir = $appDir;
    }

    public function getPayloadDir(): string
    {
        return "{$this->appDir}payload";
    }

    public function setProjectPath(string $projectPath): void
    {
        $this->projectPath = $projectPath;
    }

    public function getProjectPath(): string
    {
        return $this->projectPath;
    }

    public function setRequiredRecipes(array $requiredRecipes): void
    {
        $this->requiredRecipes = NovaCollection($requiredRecipes);
    }

    public function getRequiredRecipes(): Collection
    {
        if (null === $this->requiredRecipes) {
            $this->requiredRecipes = NovaCollection([]);
        }

        return $this->requiredRecipes;
    }

    public function setOptimizer(OptimizerInterface $optimizer): void
    {
        $this->optimizer = $optimizer;
    }
}
