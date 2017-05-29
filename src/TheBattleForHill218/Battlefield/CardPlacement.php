<?php

namespace TheBattleForHill218\Battlefield;

use TheBattleForHill218\Cards\BattlefieldCard;
use TheBattleForHill218\Cards\PlayerCard;

class CardPlacement
{
    /**
     * @var BattlefieldCard
     */
    private $card;

    /**
     * @var Position
     */
    private $position;

    /**
     * @param BattlefieldCard $card
     * @param Position $position
     */
    public function __construct(BattlefieldCard $card, Position $position)
    {
        $this->card = $card;
        $this->position = $position;
    }

    /**
     * @return int|null
     */
    public function getPlayerId()
    {
        if ($this->card instanceof PlayerCard) {
            return $this->card->getPlayerId();
        }
        return null;
    }

    /**
     * @return BattlefieldCard
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @return Position
     */
    public function getPosition()
    {
        return $this->position;
    }
}