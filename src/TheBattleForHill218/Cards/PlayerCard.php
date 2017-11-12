<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\Position;

interface PlayerCard extends Card
{
    /**
     * @param Battlefield $battlefield
     * @return Position[]
     */
    public function getPossiblePlacementPositions(Battlefield $battlefield) : array;

    /**
     * @return bool
     */
    public function alwaysStartsInHand() : bool;

    /**
     * @return int
     */
    public function getPlayerId() : int;

    /**
     * @return bool
     */
    public function attackRequiresSupport() : bool;
}
