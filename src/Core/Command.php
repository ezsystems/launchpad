<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class Command.
 */
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
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param ProjectConfiguration $configuration
     */
    public function setProjectConfiguration(ProjectConfiguration $configuration)
    {
        $this->projectConfiguration = $configuration;

        return $this;
    }

    /**
     * @param string $appDir
     */
    public function setAppDir($appDir)
    {
        $this->appDir = $appDir;
    }

    /**
     * @param string $projectPath
     */
    public function setProjectPath($projectPath)
    {
        $this->projectPath = $projectPath;
    }
}
