<?php

namespace TheBattleForHill218\Battlefield;

use Functional as F;
use TheBattleForHill218\Functional as HF;
use TheBattleForHill218\Cards\SupplyOffset;

class Battlefield
{
    /**
     * @var int
     */
    private $downwardsPlayerId;

    /**
     * @var CardPlacement[]
     */
    private $placements;

    /**
     * @param int $downwardsPlayerId
     * @param CardPlacement[] $placements
     */
    public function __construct($downwardsPlayerId, array $placements)
    {
        $foundCoords = array_unique(
            F\map($placements, function (CardPlacement $placement) {
                return (string) $placement->getPosition();
            })
        );
        if (count($foundCoords) < count($placements)) {
            throw new \InvalidArgumentException('Given placements that overlap');
        }
        if (empty($placements)) {
            throw new \InvalidArgumentException('Must provide some placements');
        }

        $this->downwardsPlayerId = $downwardsPlayerId;
        $this->placements = $placements;
    }

    /**
     * @param int $expansionAmount
     * @return Position[]
     */
    public function getUnoccupiedWithExpansion($expansionAmount)
    {
        $placedPositions = $this->getPositions();
        $xs = F\map($this->getPositions(), function (Position $position) {
            return $position->getX();
        });
        $ys = F\map($this->getPositions(), function (Position $position) {
            return $position->getY();
        });
        $topLeft = new Position(F\minimum($xs), F\maximum($ys));
        $topLeft = $topLeft->offset(-$expansionAmount, $expansionAmount);
        $bottomRight = new Position(F\maximum($xs), F\minimum($ys));
        $bottomRight = $bottomRight->offset($expansionAmount, -$expansionAmount);
        return HF\filter_to_list(
            $topLeft->gridTo($bottomRight),
            function (Position $position) use ($placedPositions) {
                return !F\contains($placedPositions, $position, false);
            }
        );
    }

    /**
     * @return Position[]
     */
    private function getPositions()
    {
        return F\map($this->placements, function (CardPlacement $placement) {
            return $placement->getPosition();
        });
    }

    /**
     * @param int $myId
     * @return Position[]
     */
    public function getPositionsOfOpponent($myId)
    {
        return F\map(
            HF\filter_to_list(
                $this->placements,
                function (CardPlacement $placement) use ($myId) {
                    return $placement->getPlayerId()->reject($myId)->isDefined();
                }
            ),
            function (CardPlacement $placement) {
                return $placement->getPosition();
            }
        );
    }

    /**
     * @param int $playerId
     * @return Position
     */
    private function getBasePosition($playerId)
    {
        if ($playerId === $this->downwardsPlayerId) {
            return new Position(0, -1);
        }
        return new Position(0, 1);
    }

    /**
     * @param int $playerId
     * @return bool
     */
    private function isBasePositionOccupied($playerId)
    {
        $basePosition = $this->getBasePosition($playerId);
        return F\some($this->placements, function (CardPlacement $p) use ($basePosition) {
            return $p->getPosition() == $basePosition;
        });
    }

    /**
     * @param int $playerId
     * @param SupplyOffset[] $supplyPattern
     * @return Position[]
     */
    public function getAllowedPositions($playerId, array $supplyPattern)
    {
        $placedPositions = $this->getPositions();
        return HF\filter_to_list(
            $this->getSuppliedPositionsByPlayerId($playerId, $supplyPattern),
            function (Position $position) use ($placedPositions) {
                return !F\contains($placedPositions, $position, false);
            }
        );
    }

    /**
     * @param int $playerId
     * @param SupplyOffset[] $supplyPattern
     * @return Position[]
     */
    private function getSuppliedPositionsByPlayerId($playerId, array $supplyPattern)
    {
        if (!$this->isBasePositionOccupied($playerId)) {
            return array($this->getBasePosition($playerId));
        }

        return HF\unique_list(
            F\flat_map(
                $this->getSuppliedPlacementsByPlayerId($playerId),
                function (CardPlacement $placement) use ($supplyPattern) {
                    return $placement->getSuppliedPositions($supplyPattern);
                }
            ),
            null,
            false
        );
    }

    /**
     * @param int $playerId
     * @return CardPlacement[]
     */
    private function getSuppliedPlacementsByPlayerId($playerId)
    {
        $basePosition = $this->getBasePosition($playerId);
        list($basePlacements, $nonBasePlacements) = HF\partition_to_lists(
            $this->getPlacementsByPlayerId($playerId),
            function (CardPlacement $p) use ($basePosition) {
                return $p->getPosition() == $basePosition;
            }
        );
        if (empty($basePlacements)) {
            return array();
        }

        return $this->suppliedPlacementsStep($basePlacements[0], $nonBasePlacements);
    }

    /**
     * @todo refactor this, it's a mess
     * @param CardPlacement $placement
     * @param CardPlacement[] $checkPlacements
     * @return CardPlacement[]
     */
    private function suppliedPlacementsStep(CardPlacement $placement, array $checkPlacements)
    {
        $position = $placement->getPosition();
        $placementsSuppliedByThis = HF\filter_to_list(
            $checkPlacements,
            function (CardPlacement $p) use ($position) {
                return F\contains($p->canBeSuppliedFrom(), $position, false);
            }
        );
        $allPlacementsSupplied = array_merge(array($placement), $placementsSuppliedByThis);

        $remainingPlacements = HF\filter_to_list(
            $checkPlacements,
            function (CardPlacement $p) use ($allPlacementsSupplied) {
                return !F\contains($allPlacementsSupplied, $p, false);
            }
        );

        $returnPlacements = array_slice($allPlacementsSupplied, 0);
        foreach ($placementsSuppliedByThis as $suppliedPlacement) {
            $checkRemainingPlacements = HF\filter_to_list(
                $remainingPlacements,
                function (CardPlacement $p) use ($returnPlacements) {
                    return !F\contains($returnPlacements, $p, false);
                }
            );
            $newPlacements = $this->suppliedPlacementsStep($suppliedPlacement, $checkRemainingPlacements);
            $remainingPlacements = HF\filter_to_list(
                $remainingPlacements,
                function (CardPlacement $p) use ($newPlacements) {
                    return !F\contains($newPlacements, $p, false);
                }
            );
            $returnPlacements = array_merge($returnPlacements, $newPlacements);
        }
        return $returnPlacements;
    }

    /**
     * @param int $playerId
     * @return CardPlacement[]
     */
    private function getPlacementsByPlayerId($playerId)
    {
        return HF\filter_to_list(
            $this->placements,
            function (CardPlacement $p) use ($playerId) {
                return $p->getPlayerId()->select($playerId)->isDefined();
            }
        );
    }
}
