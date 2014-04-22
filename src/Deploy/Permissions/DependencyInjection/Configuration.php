<?php

namespace Deploy\Permissions\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy_permissions');

        $rootNode
            ->children()
                ->arrayNode('files')->prototype('array')->prototype('scalar')->end()->end()->defaultValue(array())->end()
                ->arrayNode('folders')->prototype('array')->prototype('scalar')->end()->end()->defaultValue(array())->end()
            ->end();

        return $treeBuilder;
    }
}