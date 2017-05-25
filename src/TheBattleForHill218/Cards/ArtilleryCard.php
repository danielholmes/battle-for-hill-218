<?php

namespace TheBattleForHill218\Cards;

class ArtilleryCard extends BasePlayerCard implements BattlefieldCard
{
    /**
     * @return string
     */
    public function getTypeKey()
    {
        return 'artillery';
    }
}