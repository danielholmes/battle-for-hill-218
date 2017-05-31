<?php

namespace TheBattleForHill218\Battlefield;

use Functional as F;
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
            F\map($placements, function(CardPlacement $placement) { return (string) $placement->getPosition(); })
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
        $xs = F\map($this->getPositions(), function(Position $position) { return $position->getX(); });
        $ys = F\map($this->getPositions(), function(Position $position) { return $position->getY(); });
        $topLeft = new Position(F\minimum($xs), F\maximum($ys));
        $topLeft = $topLeft->offset(-$expansionAmount, $expansionAmount);
        $bottomRight = new Position(F\maximum($xs), F\minimum($ys));
        $bottomRight = $bottomRight->offset($expansionAmount, -$expansionAmount);
        return array_values(
            F\filter(
                $topLeft->gridTo($bottomRight),
                function(Position $position) use ($placedPositions) {
                    return !F\contains($placedPositions, $position, false);
                }
            )
        );
    }

    /**
     * @return Position[]
     */
    private function getPositions()
    {
        return F\map($this->placements, function(CardPlacement $placement) { return $placement->getPosition(); });
    }

    /**
     * @param int $myId
     * @return Position[]
     */
    public function getPositionsOfOpponent($myId)
    {
        return F\map(
            array_values(
                F\filter(
                    $this->placements,
                    function(CardPlacement $placement) use ($myId) {
                        return $placement->getPlayerId() !== null && $placement->getPlayerId() !== $myId;
                    }
                )
            ),
            function(CardPlacement $placement) { return $placement->getPosition(); }
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
     * @param SupplyOffset[] $supplyOffsets
     * @return Position[]
     */
    public function getAllowedPositions($playerId, array $supplyOffsets)
    {
        $placedPositions = $this->getPositions();
        $basePosition = $this->getBasePosition($playerId);
        if (!F\contains($placedPositions, $basePosition, false)) {
            return array($basePosition);
        }

        return array_values(
            F\unique(
                F\filter(
                    F\flat_map(
                        F\filter(
                            $this->placements,
                            function(CardPlacement $placement) use ($playerId) {
                                return $placement->getPlayerId() === $playerId;
                            }
                        ),
                        function(CardPlacement $placement) use ($supplyOffsets) {
                            return F\map(
                                $supplyOffsets,
                                function(SupplyOffset $offset) use ($placement) {
                                    return $placement->getPosition()->offset(-$offset->getX(), -$offset->getY());
                                }
                            );
                        }
                    ),
                    function(Position $position) use ($placedPositions) {
                        return !F\contains($placedPositions, $position, false);
                    }
                ),
                function(Position $position) { return (string) $position; }
            )
        );
    }
}