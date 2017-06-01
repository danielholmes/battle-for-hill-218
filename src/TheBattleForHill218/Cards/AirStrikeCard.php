<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class AirStrikeCard implements PlayerCard
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
    public function getPossiblePlacements(Battlefield $battlefield)
    {
        return $battlefield->getPositionsOfOpponent($this->getPlayerId());
    }

    /**
     * @inheritdoc
     */
    public function getTypeKey()
    {
        return 'air-strike';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName()
    {
        return 'Air Strike';
    }

    /**
     * @inheritdoc
     */
    public function alwaysStartsInHand()
    {
        return true;
    }
}
