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
    function getPossiblePlacements(Battlefield $battlefield);

    /**
     * @return boolean
     */
    function alwaysStartsInHand();

    /**
     * @return int
     */
    function getPlayerId();
}