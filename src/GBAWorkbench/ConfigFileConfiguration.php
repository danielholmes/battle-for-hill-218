<?php


namespace GBAWorkbench;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigFileConfiguration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('config')
            ->children()
                ->arrayNode('sftp')
                    ->children()
                        ->scalarNode('host')->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('pass')->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}