<?php

namespace TheBattleForHill218\Battlefield;

use Functional as F;
use TheBattleForHill218\Cards\PlayerCard;

class Battlefield
{
    /**
     * @var CardPlacement[]
     */
    private $placements;

    /**
     * @param CardPlacement[] $placements
     */
    public function __construct(array $placements)
    {
        $foundCoords = array_unique(
            F\map($placements, function(CardPlacement $placement) { return (string) $placement->getPosition(); })
        );
        if (count($foundCoords) < count($placements)) {
            throw new \InvalidArgumentException('Given placements that overlap');
        }

        $this->placements = $placements;
    }

    /**
     * @param PlayerCard $card
     * @return Position[]
     */
    public function getPossiblePlacements(PlayerCard $card)
    {
        return [
            new Position(0, 1)
        ];
    }
}