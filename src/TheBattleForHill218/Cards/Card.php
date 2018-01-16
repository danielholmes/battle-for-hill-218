<?php

namespace TheBattleForHill218\Cards;

interface Card
{
    /**
     * @return int
     */
    public function getId() : int;

    /**
     * @return string
     */
    public function getTypeKey() : string;

    /**
     * @return string
     */
    public function getTypeName() : string;
}
