<?php

namespace TheBattleForHill218\Cards;

abstract class BattlefieldPlayerCard implements PlayerCard, BattlefieldCard
{
    /**
     * @var int
     */
    private $playerId;

    /**
     * @param int $playerId
     */
    public function __construct($playerId)
    {
        $this->playerId = $playerId;
    }

    /**
     * @return int
     */
    public function getPlayerId()
    {
        return $this->playerId;
    }

    /**
     * @inheritdoc
     */
    public function alwaysStartsInHand()
    {
        return false;
    }
}