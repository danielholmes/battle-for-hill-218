<?php


namespace BGAWorkbench;


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
                ->arrayNode('testDb')
                    ->children()
                        ->scalarNode('user')->end()
                        ->scalarNode('pass')->end()
                    ->end()
                ->end()
                ->booleanNode('useComposer')
                    ->defaultFalse()
                ->end()
                ->arrayNode('extraSrc')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}