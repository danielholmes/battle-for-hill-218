<?php

namespace TheBattleForHill218\Cards;

class HillCard implements BattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return 'hill';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return 'Hill';
    }
}
