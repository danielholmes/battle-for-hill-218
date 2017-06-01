<?php

namespace TheBattleForHill218\Cards;

abstract class BasePlayerBattlefieldCard implements PlayerBattlefieldCard
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
