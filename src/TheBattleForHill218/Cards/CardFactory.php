<?php

namespace TheBattleForHill218\Cards;

class CardFactory
{
    /**
     * @param int $id
     * @param string $key
     * @param int $playerId
     * @return PlayerCard
     */
    public static function createFromTypeKey(int $id, string $key, int $playerId) : PlayerCard
    {
        switch ($key) {
            case 'air-strike':
                return new AirStrikeCard($id, $playerId);
            default:
                return self::createBattlefieldFromTypeKey($id, $key, $playerId);
        }
    }

    /**
     * @param int $id
     * @param string $key
     * @param int $playerId
     * @return BattlefieldCard
     */
    public static function createBattlefieldFromTypeKey(int $id, string $key, int $playerId) : BattlefieldCard
    {
        switch ($key) {
            case 'infantry':
                return new InfantryCard($id, $playerId);
            case 'paratroopers':
                return new ParatroopersCard($id, $playerId);
            case 'heavy-weapons':
                return new HeavyWeaponsCard($id, $playerId);
            case 'special-forces':
                return new SpecialForcesCard($id, $playerId);
            case 'tank':
                return new TankCard($id, $playerId);
            case 'artillery':
                return new ArtilleryCard($id, $playerId);
            default:
                throw new \InvalidArgumentException("Unknown type key {$key}");
        }
    }
}
