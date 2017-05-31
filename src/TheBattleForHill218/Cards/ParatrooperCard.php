<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class ParatrooperCard extends BattlefieldPlayerCard implements BattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'paratroopers';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName()
    {
        return 'Paratroopers';
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getUnoccupiedWithExpansion(1);
    }
}