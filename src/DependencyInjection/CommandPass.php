<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\DependencyInjection;

use eZ\Launchpad\Command\Test;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CommandPass.
 */
class CommandPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $env;

    /**
     * CommandPass constructor.
     *
     * @param string $env
     */
    public function __construct($env)
    {
        $this->env = $env;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (in_array($this->env, ['dev', 'test'])) {
            $definition = new Definition(Test::class);
            $definition->addTag('ezlaunchpad.command');
            $container->setDefinition('ez.launchpad.test', $definition);
        }

        $commands = $container->findTaggedServiceIds('ezlaunchpad.command');
        foreach ($commands as $id => $tags) {
            $commandDefinition = $container->getDefinition($id);
            $commandDefinition->addMethodCall('setProjectConfiguration', [new Reference('project_configuration')]);
            $commandDefinition->addMethodCall('setAppDir', [$container->getParameter('app_dir')]);
            $commandDefinition->addMethodCall('setProjectPath', [$container->getParameter('project_path')]);
        }
    }
}
