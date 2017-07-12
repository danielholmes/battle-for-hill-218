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
    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;
    }

    /**
     * @inheritdoc
     */
    public function getPlayerId() : int
    {
        return $this->playerId;
    }

    /**
     * @inheritdoc
     */
    public function alwaysStartsInHand() : bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        $refClass = new \ReflectionClass($this);
        return $refClass->getShortName() . '()';
    }
}
