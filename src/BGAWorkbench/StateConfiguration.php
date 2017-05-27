<?php


namespace BGAWorkbench;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class StateConfiguration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        /*
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.*/

        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('machinestates')
            ->prototype('array')
                ->children()
                    ->scalarNode('name')->isRequired()->end()
                    ->scalarNode('description')->end()
                    ->scalarNode('descriptionmyturn')->end()
                    ->scalarNode('action')->end()
                    ->arrayNode('possibleactions')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('transitions')
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('args')->end()
                    ->booleanNode('updateGameProgression')->end()
                    ->enumNode('type')
                        ->isRequired()
                        ->values(['activeplayer', 'multipleactiveplayer', 'game', 'manager'])
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}