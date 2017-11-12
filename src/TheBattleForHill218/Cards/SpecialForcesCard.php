<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class SpecialForcesCard extends BasePlayerBattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return 'special-forces';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return 'Special Forces';
    }

    /**
     * @inheritdoc
     */
    public function getSupplyPattern() : array
    {
        return SupplyOffset::crossPattern();
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
    public function attackRequiresSupport() : bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAttackPattern() : array
    {
        return AttackOffset::plusPattern();
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacementPositions(Battlefield $battlefield) : array
    {
        return $battlefield->getSuppliedPlaceablePositions($this->getPlayerId(), $this->getSupplyPattern());
    }
}
