<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class ArtilleryCard extends BasePlayerBattlefieldCard
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
        return AttackOffset::arrowPattern();
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
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getAllowedPositions($this->getPlayerId(), $this->getSupplyPattern());
    }
}
