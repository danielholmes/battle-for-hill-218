<?php

namespace TheBattleForHill218\Cards;

use TheBattleForHill218\Battlefield\Battlefield;

class AirStrikeCard implements PlayerCard
{
    /**
     * @return string
     */
    public static function typeName()
    {
        return clienttranslate('Air Strike');
    }

    /**
     * @return string
     */
    public static function typeKey()
    {
        return 'air-strike';
    }

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $playerId;

    /**
     * @param int $id
     * @param int $playerId
     */
    public function __construct(int $id, int $playerId)
    {
        $this->id = $id;
        $this->playerId = $playerId;
    }

    /**
     * @inheritdoc
     */
    public function getId() : int
    {
        return $this->id;
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
    public function getPossiblePlacementPositions(Battlefield $battlefield) : array
    {
        return $battlefield->getPositionsOfOpponent($this->getPlayerId());
    }

    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return self::typeKey();
    }

    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return self::typeName();
    }

    /**
     * @inheritdoc
     */
    public function attackRequiresSupport() : bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function alwaysStartsInHand() : bool
    {
        return true;
    }
}
