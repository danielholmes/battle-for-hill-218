<?php

namespace TheBattleForHill218\Cards;

abstract class BattlefieldCard implements Card
{
    /**
     * @inheritdoc
     */
    public function alwaysStartsInHand()
    {
        return false;
    }
}