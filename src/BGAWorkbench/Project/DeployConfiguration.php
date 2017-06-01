<?php

namespace BGAWorkbench\Project;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DeployConfiguration implements ConfigurationInterface
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
