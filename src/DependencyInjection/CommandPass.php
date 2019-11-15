<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\DependencyInjection;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use eZ\Launchpad\Tests\Command\Test;
use eZ\Launchpad\Tests\Command\TestDockerClient;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CommandPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $env;

    public function __construct(string $env)
    {
        $this->env = $env;
    }

    public function process(ContainerBuilder $container): void
    {
        if (\in_array($this->env, ['dev', 'test'])) {
            $definition = new Definition(Test::class);
            $definition->addTag('ezlaunchpad.command');
            $definition->setPublic(true);
            $container->setDefinition('ez.launchpad.test', $definition);
            $definition = new Definition(TestDockerClient::class);
            $definition->addTag('ezlaunchpad.command');
            $definition->setPublic(true);
            $container->setDefinition('ez.launchpad.testdockerclient', $definition);
        }

        $commands = $container->findTaggedServiceIds('ezlaunchpad.command');
        foreach ($commands as $id => $tags) {
            $commandDefinition = $container->getDefinition($id);
            $commandDefinition->addMethodCall('setProjectConfiguration', [new Reference(ProjectConfiguration::class)]);
            $commandDefinition->addMethodCall('setAppDir', [$container->getParameter('app_dir')]);
            $commandDefinition->addMethodCall('setProjectPath', [$container->getParameter('project_path')]);
        }
    }
}
