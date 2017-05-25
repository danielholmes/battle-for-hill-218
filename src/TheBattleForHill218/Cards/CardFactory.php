<?php

namespace TheBattleForHill218\Cards;

class CardFactory
{
    /**
     * @var Card[]|null
     */
    private static $instances = null;

    /**
     * @return Card[]
     */
    private static function instances()
    {
        if (self::$instances === null) {
            self::$instances = array(
                new AirStrikeCard(),
                new InfantryCard(),
                new ParatrooperCard(),
                new HeavyWeaponsCard(),
                new SpecialForcesCard(),
                new TankCard(),
                new ArtilleryCard()
            );
        }
        return self::$instances;
    }

    /**
     * @param string $key
     * @return Card
     */
    public static function createFromTypeKey($key)
    {
        foreach (self::instances() as $card) {
            if ($card->getTypeKey() === $key) {
                return $card;
            }
        }
        throw new \InvalidArgumentException("Unknown type key {$key}");
    }
}