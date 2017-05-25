<?php

namespace TheBattleForHill218\Cards;

class AirStrikeCard implements Card
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