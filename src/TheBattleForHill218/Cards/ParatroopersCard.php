<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Functional as BF;

class ParatroopersCard extends BasePlayerBattlefieldCard
{
    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return 'paratroopers';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return 'Paratroopers';
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
    public function getPossiblePlacements(Battlefield $battlefield) : array
    {
        $opponentBasePosition = $battlefield->getOpponentBasePosition($this->getPlayerId());
        return BF\filter_to_list(
            $battlefield->getUnoccupiedWithExpansion(2),
            function (Position $position) use ($opponentBasePosition) {
                return $position != $opponentBasePosition;
            }
        );
    }
}
