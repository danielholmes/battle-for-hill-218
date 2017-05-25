<?php

namespace TheBattleForHill218\Cards;

class InfantryCard extends BasePlayerCard implements BattlefieldCard
{
    /**
     * @return string
     */
    public function getTypeKey()
    {
        return 'infantry';
    }
}