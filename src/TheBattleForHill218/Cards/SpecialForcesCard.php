<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class SpecialForcesCard extends BattlefieldPlayerCard implements BattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'special-forces';
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getAllowedPositions($this->getPlayerId(), SupplyOffset::createCrossConfig());
    }
}