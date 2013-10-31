<?php

namespace Deploy\Maintenance\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy_maintenance');

        $rootNode
            ->children()
                ->scalarNode('template_file')->isRequired()->end()
                ->scalarNode('target_file')->isRequired()->end()
            ->end();

        return $treeBuilder;
    }
}