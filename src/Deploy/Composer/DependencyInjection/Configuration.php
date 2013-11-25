<?php

namespace Deploy\Composer\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy_composer[');

        $rootNode
            ->children()
                ->scalarNode('binary_path')->defaultValue('composer.phar')->end()
                ->arrayNode('options')->prototype('scalar')->end()->defaultValue(array('--verbose', '--prefer-dist', '-o'))->end()
                ->booleanNode('update_vendors')->defaultValue(false)->end()
                ->arrayNode('working_dirs')->prototype('scalar')->end()->defaultValue(array('.'))->end()
                ->integerNode('timeout')->defaultValue(1000)->end()
            ->end();

        return $treeBuilder;
    }
}