<?php

namespace TheBattleForHill218\Cards;

interface Card
{
    /**
     * @return string
     */
    public function getTypeKey();

    /**
     * @return string
     */
    public function getTypeName();
}
