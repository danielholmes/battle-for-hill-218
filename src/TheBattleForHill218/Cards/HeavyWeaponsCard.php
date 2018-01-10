<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class HeavyWeaponsCard extends BasePlayerBattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return 'heavy-weapons';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return clienttranslate('Heavy Weapons');
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
    public function getSupplyPattern() : array
    {
        return SupplyOffset::plusPattern();
    }

    /**
     * @inheritdoc
     */
    public function getSupportPattern() : array
    {
        return SupportOffset::borderPattern();
    }

    /**
     * @inheritdoc
     */
    public function getAttackPattern() : array
    {
        return AttackOffset::crossPattern();
    }

    /**
     * @inheritdoc
     */
    public function getPossiblePlacementPositions(Battlefield $battlefield) : array
    {
        return $battlefield->getSuppliedPlaceablePositions($this->getPlayerId(), $this->getSupplyPattern());
    }
}
