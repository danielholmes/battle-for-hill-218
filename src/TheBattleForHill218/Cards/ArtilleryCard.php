<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class ArtilleryCard extends BattlefieldPlayerCard implements BattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeName()
    {
        return 'Artillery';
    }

    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'artillery';
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getAllowedPositions($this->getPlayerId(), SupplyOffset::createPlusConfig());
    }
}
