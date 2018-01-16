<?php

namespace TheBattleForHill218;

use TheBattleForHill218\Cards\CardFactory;
use TheBattleForHill218\Cards\PlayerCard;
use Functional as F;
use TheBattleForHill218\Functional as HF;

class Hill218Setup
{
    const NUMBER_OF_PLAYERS = 2;

    const PLAYABLE_CARDS_SIZE = 7;

    const NUMBER_OF_INITIAL_CARDS_TO_RETURN = 2;

    /**
     * @param int $playerId
     * @param int $idStart
     * @return PlayerCard[][]
     */
    public static function getPlayerStartingCards(int $playerId, int $idStart) : array
    {
        $all = self::createAllStartingCards($playerId, $idStart);
        shuffle($all);
        list($required, $remaining) = HF\partition_to_lists(
            $all,
            function (PlayerCard $card) {
                return $card->alwaysStartsInHand();
            }
        );

        if (count($required) > self::PLAYABLE_CARDS_SIZE) {
            throw new \LogicException('More required cards than initial hand size');
        }

        $hand = array_merge(
            $required,
            array_slice($remaining, 0, self::PLAYABLE_CARDS_SIZE - count($required))
        );
        $deck = HF\filter_to_list($all, function (PlayerCard $card) use ($hand) {
            return !F\contains($hand, $card);
        });
        return [$hand, $deck];
    }

    /**
     * @param int $playerId
     * @param int $idStart
     * @return PlayerCard[]
     */
    private static function createAllStartingCards(int $playerId, int $idStart) : array
    {
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
            function ($typeKey, $i) use ($playerId, $idStart) {
                return CardFactory::createFromTypeKey($idStart + $i, $typeKey, $playerId);
            }
        );
    }

    /**
     * @return int
     */
    public static function getTotalDeckSize() : int
    {
        return self::getNumberOfStartingCardsPerPlayer() * self::NUMBER_OF_PLAYERS;
    }

    /**
     * @return int
     */
    public static function getNumberOfStartingCardsPerPlayer() : int
    {
        return count(self::createAllStartingCards(0, 0));
    }

    /**
     * @return int
     */
    public static function getPlayerDeckSizeAfterInitialReturn() : int
    {
        return self::getNumberOfStartingCardsPerPlayer() + self::NUMBER_OF_INITIAL_CARDS_TO_RETURN -
            self::PLAYABLE_CARDS_SIZE;
    }
}
