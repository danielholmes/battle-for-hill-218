<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class SpecialForcesBattlefieldCard extends BasePlayerBattlefieldCard
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
    public function getTypeName()
    {
        return 'Special Forces';
    }

    /**
     * @inheritdoc
     */
    public function getSupplyPattern()
    {
        return SupplyOffset::crossPattern();
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
    public function attackRequiresSupport()
    {
        return true;
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
