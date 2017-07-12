<?php

namespace TheBattleForHill218\Battlefield;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use TheBattleForHill218\Cards\AttackOffset;
use TheBattleForHill218\Cards\BattlefieldCard;
use TheBattleForHill218\Cards\PlayerBattlefieldCard;
use TheBattleForHill218\Cards\PlayerCard;
use TheBattleForHill218\Cards\SupplyOffset;
use Functional as F;
use TheBattleForHill218\Cards\SupportOffset;

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

        return [];
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
     * @return Position[]
     */
    public function getAttackPositions()
    {
        if ($this->card instanceof PlayerBattlefieldCard) {
            return $this->getAttackPositionsByPattern($this->card->getAttackPattern());
        }

        return [];
    }

    /**
     * @return Position[]
     */
    public function getYFlippedAttackPositions()
    {
        if ($this->card instanceof PlayerBattlefieldCard) {
            return $this->getAttackPositionsByPattern(
                F\map(
                    $this->card->getAttackPattern(),
                    function (AttackOffset $o) {
                        return $o->flipY();
                    }
                )
            );
        }

        return [];
    }

    /**
     * @param AttackOffset[] $pattern
     * @return Position[]
     */
    private function getAttackPositionsByPattern(array $pattern)
    {
        $position = $this->position;
        return F\map(
            $pattern,
            function (AttackOffset $o) use ($position) {
                return $position->offset($o->getX(), $o->getY());
            }
        );
    }

    /**
     * @return Position[]
     */
    public function getSupportPositions()
    {
        if ($this->card instanceof PlayerBattlefieldCard) {
            $position = $this->position;
            return F\map(
                $this->card->getSupportPattern(),
                function (SupportOffset $o) use ($position) {
                    return $position->offset($o->getX(), $o->getY());
                }
            );
        }

        return [];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "CardPlacement(card={$this->card}, position={$this->position})";
    }
}
