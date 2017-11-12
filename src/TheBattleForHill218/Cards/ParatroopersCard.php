<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Functional as BF;
use Functional as F;

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
    public function getPossiblePlacementPositions(Battlefield $battlefield) : array
    {
        $allPositions = $battlefield->getUnoccupiedWithExpansion(2);
        $placeable = $battlefield->getSuppliedPlaceablePositions($this->getPlayerId(), $this->getSupplyPattern());
        if (F\contains($placeable, $battlefield->getOpponentBasePosition($this->getPlayerId()), false)) {
            return $allPositions;
        }

        $opponentBasePosition = $battlefield->getOpponentBasePosition($this->getPlayerId());
        return BF\filter_to_list(
            $allPositions,
            function (Position $position) use ($opponentBasePosition) {
                return $position != $opponentBasePosition;
            }
        );
    }
}
