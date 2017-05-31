<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class TankCard extends BattlefieldPlayerCard implements BattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'tank';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName()
    {
        return 'Tank';
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getAllowedPositions($this->getPlayerId(), SupplyOffset::createPlusConfig());
    }
}