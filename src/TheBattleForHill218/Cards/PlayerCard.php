<?php

namespace TheBattleForHill218\Cards;

interface PlayerCard extends Card
{
    /**
     * @return boolean
     */
    function alwaysStartsInHand();

    /**
     * @return int
     */
    function getPlayerId();
}