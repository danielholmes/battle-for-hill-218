<?php

namespace TheBattleForHill218\Cards;

class ParatrooperCard extends BasePlayerCard implements BattlefieldCard
{
    /**
     * @return string
     */
    public function getTypeKey()
    {
        return 'paratroopers';
    }
}