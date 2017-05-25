<?php

namespace TheBattleForHill218\Cards;

class AirStrikeCard extends BasePlayerCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'air-strike';
    }

    /**
     * @inheritdoc
     */
    public function alwaysStartsInHand()
    {
        return true;
    }
}