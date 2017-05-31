<?php

namespace TheBattleForHill218\Cards;

class HillCard implements BattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'hill';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName()
    {
        return 'Hill';
    }
}