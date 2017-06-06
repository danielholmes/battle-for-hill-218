<?php

namespace TheBattleForHill218\Battlefield;

use Functional as F;
use PhpOption\Option;
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
        if (empty($placements)) {
            throw new \InvalidArgumentException('Must provide some placements');
        }
        $foundCoords = F\unique(
            F\map(
                $placements,
                function (CardPlacement $p) {
                    return $p->getPosition();
                }
            ),
            null,
            false
        );
        if (count($foundCoords) < count($placements)) {
            throw new \InvalidArgumentException('Given placements that overlap');
        }

        $this->downwardsPlayerId = $downwardsPlayerId;
        $this->placements = $placements;
    }

    /**
     * @param Position $placementPosition
     * @return boolean
     */
    public function hasAttackablePlacement(Position $placementPosition)
    {
        try {
            $attackable = $this->getAttackablePlacements($placementPosition);
            return !empty($attackable);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param Position $placementPosition
     * @return CardPlacement[]
     * @throws \InvalidArgumentException
     */
    public function getAttackablePlacements(Position $placementPosition)
    {
        $placement = Option::fromValue(
            F\first(
                $this->placements,
                function (CardPlacement $p) use ($placementPosition) {
                    return $p->getPosition() == $placementPosition;
                }
            )
        )->getOrThrow(new \InvalidArgumentException('No placement found'));

        if (!F\contains($this->placements, $placement, false)) {
            throw new \InvalidArgumentException('Placement not on battlefield');
        }

        $myId = $placement->getPlayerId()->getOrThrow(new \InvalidArgumentException('Not a player placement'));
        $opponentPlacements = HF\filter_to_list($this->placements, function (CardPlacement $p) use ($myId) {
            return $p->getPlayerId()->isDefined() && $p->getPlayerId()->select($myId)->isEmpty();
        });

        $attackPositions = $this->getAttackablePositions($placement);
        return HF\filter_to_list(
            $opponentPlacements,
            function (CardPlacement $p) use ($attackPositions) {
                return F\contains($attackPositions, $p->getPosition(), false);
            }
        );
    }

    /**
     * @param CardPlacement $placement
     * @return Position[]
     */
    private function getAttackablePositions(CardPlacement $placement)
    {
        $myId = $placement->getPlayerId()->getOrThrow(new \InvalidArgumentException('Not a player placement'));
        if ($myId === $this->downwardsPlayerId) {
            $attackPositions = $placement->getAttackPositions();
        } else {
            $attackPositions = $placement->getYFlippedAttackPositions();
        }
        if (!$placement->getCard()->attackRequiresSupport()) {
            return $attackPositions;
        }

        $myPlacements = HF\filter_to_list($this->placements, function (CardPlacement $p) use ($placement, $myId) {
            return $p->getPlayerId()->select($myId)->isDefined() && $p != $placement;
        });
        $supportedPositions = HF\unique_list(
            F\flat_map(
                $myPlacements,
                function (CardPlacement $p) {
                    return $p->getSupportPositions();
                }
            ),
            null,
            false
        );
        return array_intersect($attackPositions, $supportedPositions);
    }

    /**
     * @param int $expansion
     * @return Position[]
     */
    public function getUnoccupiedWithExpansion($expansion)
    {
        $placedPositions = $this->getPositions();
        $xs = F\map($this->getPositions(), function (Position $position) {
            return $position->getX();
        });
        $ys = F\map($this->getPositions(), function (Position $position) {
            return $position->getY();
        });
        $topLeft = new Position(F\minimum($xs), F\maximum($ys));
        $topLeft = $topLeft->offset(-$expansion, $expansion);
        $bottomRight = new Position(F\maximum($xs), F\minimum($ys));
        $bottomRight = $bottomRight->offset($expansion, -$expansion);
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
