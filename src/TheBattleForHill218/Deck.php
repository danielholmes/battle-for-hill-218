<?php

namespace TheBattleForHill218;

use TheBattleForHill218\Cards\Card;
use TheBattleForHill218\Cards\CardFactory;

class Deck
{
    /**
     * @return Card[]
     */
    public static function getStartingCards() {
        return array_map(
            function($typeKey) { return CardFactory::createFromTypeKey($typeKey); },
            array_merge(
                array_fill(0, 7, 'infantry'),
                array_fill(0, 5, 'heavy-weapons'),
                array_fill(0, 3, 'special-forces'),
                array_fill(0, 3, 'tank'),
                array_fill(0, 3, 'artillery'),
                array_fill(0, 3, 'paratrooper'),
                array_fill(0, 2, 'air-strike')
            )
        );
    }
}