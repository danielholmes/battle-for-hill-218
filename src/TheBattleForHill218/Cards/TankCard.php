<?php

namespace TheBattleForHill218\Cards;

class TankCard extends BasePlayerCard implements BattlefieldCard
{
    /**
     * @return string
     */
    public function getTypeKey()
    {
        return 'tank';
    }
}