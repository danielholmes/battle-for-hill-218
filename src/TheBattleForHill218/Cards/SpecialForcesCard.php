<?php

namespace TheBattleForHill218\Cards;

class SpecialForcesCard extends BasePlayerCard implements BattlefieldCard
{
    /**
     * @return string
     */
    public function getTypeKey()
    {
        return 'special-forces';
    }
}