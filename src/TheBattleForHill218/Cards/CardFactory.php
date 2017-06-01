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
            default:
                return self::createBattlefieldFromTypeKey($key, $playerId);
        }
    }

    /**
     * @param string $key
     * @param int $playerId
     * @return BattlefieldCard
     */
    public static function createBattlefieldFromTypeKey($key, $playerId)
    {
        switch ($key) {
            case 'infantry':
                return new InfantryBattlefieldCard($playerId);
            case 'paratroopers':
                return new ParatrooperBattlefieldCard($playerId);
            case 'heavy-weapons':
                return new HeavyWeaponsBattlefieldCard($playerId);
            case 'special-forces':
                return new SpecialForcesBattlefieldCard($playerId);
            case 'tank':
                return new TankBattlefieldCard($playerId);
            case 'artillery':
                return new ArtilleryBattlefieldCard($playerId);
            default:
                throw new \InvalidArgumentException("Unknown type key {$key}");
        }
    }
}
