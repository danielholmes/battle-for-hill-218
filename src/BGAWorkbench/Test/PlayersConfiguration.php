<?php

namespace BGAWorkbench\Test;

use Faker\Factory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class PlayersConfiguration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        $faker = Factory::create();

        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('players')
            ->prototype('array')
                ->children()
                    ->scalarNode('player_id')->end()
                    ->scalarNode('player_canal')->defaultValue(md5(time()))->end()
                    ->scalarNode('player_color')->defaultValue('ff0000')->end()
                    ->scalarNode('player_name')->defaultValue($faker->firstName)->end()
                    ->scalarNode('player_avatar')->defaultValue('000000')->end()
                    ->scalarNode('player_is_admin')->defaultValue(0)->end()
                    ->scalarNode('player_beginner')->defaultValue(0)->end()
                    ->scalarNode('player_is_ai')->defaultValue(0)->end()
                    ->scalarNode('player_table_order')->defaultValue(0)->end() // TODO: Should be sequential
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
