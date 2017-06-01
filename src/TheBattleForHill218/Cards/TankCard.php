<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class TankCard extends BasePlayerBattlefieldCard
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
    public function attackRequiresSupport()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSupplyPattern()
    {
        return SupplyOffset::plusPattern();
    }

    /**
     * @inheritdoc
     */
    public function getSupportPattern()
    {
        return SupportOffset::plusPattern();
    }

    /**
     * @inheritdoc
     */
    public function getAttackPattern()
    {
        return AttackOffset::plusPattern();
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getAllowedPositions($this->getPlayerId(), $this->getSupplyPattern());
    }
}
