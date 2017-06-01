<?php

namespace TheBattleForHill218\Battlefield;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use TheBattleForHill218\Cards\BattlefieldCard;
use TheBattleForHill218\Cards\PlayerBattlefieldCard;
use TheBattleForHill218\Cards\PlayerCard;
use TheBattleForHill218\Cards\SupplyOffset;
use Functional as F;

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
     * @return Option
     */
    public function getPlayerId()
    {
        if ($this->card instanceof PlayerCard) {
            return new Some($this->card->getPlayerId());
        }
        return None::create();
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

    /**
     * @return Position[]
     */
    public function canBeSuppliedFrom()
    {
        if ($this->card instanceof PlayerBattlefieldCard) {
            $position = $this->getPosition();
            return F\map(
                $this->card->getSupplyPattern(),
                function (SupplyOffset $o) use ($position) {
                    return $position->offset(-$o->getX(), $o->getY());
                }
            );
        }

        return array();
    }

    /**
     * @param SupplyOffset[] $supplyPattern
     * @return Position[]
     */
    public function getSuppliedPositions(array $supplyPattern)
    {
        $position = $this->getPosition();
        return F\map(
            $supplyPattern,
            function (SupplyOffset $offset) use ($position) {
                return $position->offset(-$offset->getX(), -$offset->getY());
            }
        );
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "CardPlacement(card={$this->card}, position={$this->position})";
    }
}
