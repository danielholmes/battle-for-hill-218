<?php

namespace TheBattleForHill218\Cards;

class CardFactory
{
    /**
     * @param string $key
     * @param int $playerId
     * @return PlayerCard
     */
    public static function createFromTypeKey($key, $playerId)
    {
        switch ($key) {
            case 'air-strike':
                return new AirStrikeCard($playerId);
            case 'infantry':
                return new InfantryCard($playerId);
            case 'paratroopers':
                return new ParatrooperCard($playerId);
            case 'heavy-weapons':
                return new HeavyWeaponsCard($playerId);
            case 'special-forces':
                return new SpecialForcesCard($playerId);
            case 'tank':
                return new TankCard($playerId);
            case 'artillery':
                return new ArtilleryCard($playerId);
        }
        throw new \InvalidArgumentException("Unknown type key {$key}");
    }
}