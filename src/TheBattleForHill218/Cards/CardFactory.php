<?php

namespace TheBattleForHill218\Cards;

class CardFactory
{
    /**
     * @param string $key
     * @param int $playerId
     * @return PlayerCard
     */
    public static function createFromTypeKey(string $key, int $playerId) : PlayerCard
    {
        switch ($key) {
            case 'air-strike':
                return new AirStrikeCard($playerId);
            default:
                return self::createBattlefieldFromTypeKey($key, $playerId);
        }
    }

    /**
     * @param string $key
     * @param int $playerId
     * @return BattlefieldCard
     */
    public static function createBattlefieldFromTypeKey(string $key, int $playerId) : BattlefieldCard
    {
        switch ($key) {
            case 'infantry':
                return new InfantryCard($playerId);
            case 'paratroopers':
                return new ParatroopersCard($playerId);
            case 'heavy-weapons':
                return new HeavyWeaponsCard($playerId);
            case 'special-forces':
                return new SpecialForcesCard($playerId);
            case 'tank':
                return new TankCard($playerId);
            case 'artillery':
                return new ArtilleryCard($playerId);
            default:
                throw new \InvalidArgumentException("Unknown type key {$key}");
        }
    }
}
