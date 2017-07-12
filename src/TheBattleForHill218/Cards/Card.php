<?php

namespace TheBattleForHill218\Cards;

interface Card
{
    /**
     * @return string
     */
    public function getTypeKey() : string;

    /**
     * @return string
     */
    public function getTypeName() : string;
}
