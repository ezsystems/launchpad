<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('ezlaunchpad');
        $rootNode
            ->children()
                ->arrayNode('docker')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('compose_filename')->defaultValue('docker-compose.yml')->end()
                        ->scalarNode('network_name')->defaultValue('default-ezlaunchpad')->end()
                        ->scalarNode('network_prefix_port')->defaultValue('42')->end()
                        ->scalarNode('host_machine_mapping')->defaultNull()->end()
                        ->scalarNode('host_composer_cache_dir')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('provisioning')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('folder_name')->defaultValue('provisioning')->end()
                    ->end()
                ->end()
                ->arrayNode('composer')
                    ->children()
                        ->arrayNode('http_basic')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('host')->defaultNull()->end()
                                    ->scalarNode('login')->defaultNull()->end()
                                    ->scalarNode('password')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('last_update_check')->defaultNull()->end()
            ->end();

        return $treeBuilder;
    }
}
