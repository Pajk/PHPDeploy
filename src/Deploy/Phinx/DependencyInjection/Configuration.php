<?php

namespace Deploy\Phinx\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy_phinx');

        $rootNode
            ->children()
                ->scalarNode('binary_path')->defaultValue('bin/phinx')->end()
                ->arrayNode('config_files')->prototype('scalar')->end()->defaultValue(array('phinx.yml'))->end()
            ->end();

        return $treeBuilder;
    }
}