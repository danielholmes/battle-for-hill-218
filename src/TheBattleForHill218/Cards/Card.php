<?php

namespace TheBattleForHill218\Cards;

interface Card
{
    /**
     * @return string
     */
    function getTypeKey();

    /**
     * @return string
     */
    function getTypeName();
}