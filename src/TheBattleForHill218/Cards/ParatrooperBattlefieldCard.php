<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class ParatrooperBattlefieldCard extends BasePlayerBattlefieldCard
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
    public function attackRequiresSupport()
    {
        return true;
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
        return $battlefield->getUnoccupiedWithExpansion(1);
    }
}
