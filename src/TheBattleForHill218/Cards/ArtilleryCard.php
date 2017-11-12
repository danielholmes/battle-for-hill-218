<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class ArtilleryCard extends BasePlayerBattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return 'Artillery';
    }

    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return 'artillery';
    }

    /**
     * @inheritdoc
     */
    public function getSupplyPattern() : array
    {
        return SupplyOffset::plusPattern();
    }

    /**
     * @inheritdoc
     */
    public function getSupportPattern() : array
    {
        return SupportOffset::plusPattern();
    }

    /**
     * @inheritdoc
     */
    public function getAttackPattern() : array
    {
        return AttackOffset::arrowPattern();
    }

    /**
     * @inheritdoc
     */
    public function attackRequiresSupport() : bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacementPositions(Battlefield $battlefield) : array
    {
        return $battlefield->getSuppliedPlaceablePositions($this->getPlayerId(), $this->getSupplyPattern());
    }
}
