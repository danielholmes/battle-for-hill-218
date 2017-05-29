<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class InfantryCard extends BattlefieldPlayerCard implements BattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'infantry';
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getAllowedPositions($this->getPlayerId(), SupplyOffset::createPlusConfig());
    }
}