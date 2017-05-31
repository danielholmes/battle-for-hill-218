<?php

namespace TheBattleForHill218;

use TheBattleForHill218\Cards\AirStrikeCard;
use TheBattleForHill218\Cards\ArtilleryCard;
use TheBattleForHill218\Cards\CardFactory;
use TheBattleForHill218\Cards\HeavyWeaponsCard;
use TheBattleForHill218\Cards\InfantryCard;
use TheBattleForHill218\Cards\ParatrooperCard;
use TheBattleForHill218\Cards\PlayerCard;
use Functional as F;
use TheBattleForHill218\Cards\SpecialForcesCard;
use TheBattleForHill218\Cards\TankCard;

class Hill218Setup
{
    const HAND_SIZE = 7;
    const NUMBER_OF_INITIAL_CARDS_TO_RETURN = 2;

    /**
     * @param int $playerId
     * @return PlayerCard[][]
     */
    public static function getPlayerStartingCards($playerId)
    {
        $all = self::createAllStartingCards($playerId);
        shuffle($all);
        list($required, $remaining) = F\partition(
            $all,
            function(PlayerCard $card) { return $card->alwaysStartsInHand(); }
        );

        if (count($required) > self::HAND_SIZE) {
            throw new \LogicException('More required cards than initial hand size');
        }

        $hand = array_merge($required, array_slice($remaining, 0, self::HAND_SIZE - count($required)));
        $deck = array_values(
            F\filter($all, function(PlayerCard $card) use ($hand) { return !F\contains($hand, $card); })
        );
        return array($hand, $deck);
    }

    /**
     * @param int $playerId
     * @return PlayerCard[]
     */
    private static function createAllStartingCards($playerId) {
        return F\map(
            array_merge(
                array_fill(0, 7, 'infantry'),
                array_fill(0, 5, 'heavy-weapons'),
                array_fill(0, 3, 'special-forces'),
                array_fill(0, 3, 'tank'),
                array_fill(0, 3, 'artillery'),
                array_fill(0, 3, 'paratroopers'),
                array_fill(0, 2, 'air-strike')
            ),
            function($typeKey) use ($playerId) { return CardFactory::createFromTypeKey($typeKey, $playerId); }
        );
    }
}