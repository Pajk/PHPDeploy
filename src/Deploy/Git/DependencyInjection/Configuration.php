<?php

namespace Deploy\Git\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy_git');

        $rootNode
            ->children()
                ->scalarNode('cached_copy_dir')->defaultValue('cached-copy')->end()
                ->scalarNode('branch')->defaultValue('master')->end()
                ->scalarNode('repository')->isRequired()->end()
                ->scalarNode('binary_path')->defaultValue('git')->end()
                ->booleanNode('enable_submodules')->defaultValue(false)->end()
            ->end();

        return $treeBuilder;
    }
}