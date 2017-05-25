<?php

namespace TheBattleForHill218\Cards;

class HeavyWeaponsCard extends BasePlayerCard implements BattlefieldCard
{
    /**
     * @return string
     */
    public function getTypeKey()
    {
        return 'heavy-weapons';
    }
}