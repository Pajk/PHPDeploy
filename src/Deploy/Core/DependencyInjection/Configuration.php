<?php

namespace Deploy\Core\DependencyInjection;

use Monolog\Logger;
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
                ->scalarNode('project_name')->isRequired()->end()
                ->scalarNode('deploy_path')->isRequired()->end()
                ->integerNode('history')->defaultValue(5)->end()
                ->scalarNode('logger_file')->defaultValue('deploy.log')->end()
                ->scalarNode('logger_level')->defaultValue(Logger::DEBUG)->end()
                ->scalarNode('logger_echo_level')->defaultValue(Logger::INFO)->end()
                ->scalarNode('logger_name')->defaultValue('deploy')->end()
                ->arrayNode('post_deploy_commands')->prototype('scalar')->end()->defaultValue(array())->end()
            ->end();

        return $treeBuilder;
    }
}
