<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class TankCard extends BasePlayerBattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return 'tank';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return clienttranslate('Tank');
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
