<?php

namespace TheBattleForHill218\Cards;

interface Card
{
    /**
     * @return string
     */
    function getTypeKey();

    /**
     * @return boolean
     */
    function alwaysStartsInHand();
}