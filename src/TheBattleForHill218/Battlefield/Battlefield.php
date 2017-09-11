<?php

namespace TheBattleForHill218\Battlefield;

use TheBattleForHill218\Cards\SupplyOffset;

interface Battlefield
{
    /**
     * @param int $myId
     * @return Position[]
     */
    public function getPositionsOfOpponent(int $myId) : array;

    /**
     * @param int $myId
     * @return Position
     */
    public function getOpponentBasePosition(int $myId) : Position;

    /**
     * @param int $playerId
     * @param SupplyOffset[] $supplyPattern
     * @return Position[]
     */
    public function getAllowedPositions(int $playerId, array $supplyPattern) : array;

    /**
     * @param int $expansion
     * @return Position[]
     */
    public function getUnoccupiedWithExpansion(int $expansion) : array;
}
