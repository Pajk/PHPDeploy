<?php

namespace Deploy\Shared\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy_shared');

        $rootNode
            ->children()
                ->arrayNode('files')->prototype('scalar')->end()->defaultValue(array())->end()
                ->arrayNode('folders')->prototype('scalar')->end()->defaultValue(array())->end()
                ->arrayNode('template_extensions')
                    ->prototype('scalar')->end()
                    ->defaultValue(array('example', 'dist', 'template', 'default'))
            ->end();

        return $treeBuilder;
    }
}