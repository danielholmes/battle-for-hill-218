<?php

namespace TheBattleForHill218\Tests\Battlefield;

use PHPUnit\Framework\TestCase;
use TheBattleForHill218\Battlefield\BattlefieldImpl;
use TheBattleForHill218\Battlefield\CardPlacement;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Cards\HillCard;
use TheBattleForHill218\Cards\InfantryCard;
use TheBattleForHill218\Cards\ParatroopersCard;

class ParatroopersCardTest extends TestCase
{
    public function testGetPossiblePlacements()
    {
        $battlefield = new BattlefieldImpl(
            2,
            [
                new CardPlacement(new HillCard(), new Position(0, 0)),
                new CardPlacement(new InfantryCard(2), new Position(0, -2)),
                new CardPlacement(new InfantryCard(1), new Position(0, 1))
            ]
        );
        $card = new ParatroopersCard(1);

        assertThat(
            $card->getPossiblePlacementPositions($battlefield),
            containsInAnyOrder(
                new Position(-2, 3),
                new Position(-1, 3),
                new Position(0, 3),
                new Position(1, 3),
                new Position(2, 3),
                //
                new Position(-2, 2),
                new Position(-1, 2),
                new Position(0, 2),
                new Position(1, 2),
                new Position(2, 2),
                //
                new Position(-2, 1),
                new Position(-1, 1),
                new Position(1, 1),
                new Position(2, 1),
                //
                new Position(-2, 0),
                new Position(-1, 0),
                new Position(1, 0),
                new Position(2, 0),
                //
                new Position(-2, -1),
                new Position(-1, -1),
                //new Position(0, -1), // Opponent base
                new Position(1, -1),
                new Position(2, -1),
                //
                new Position(-2, -2),
                new Position(-1, -2),
                // new Position(0, -2), Card in this position
                new Position(1, -2),
                new Position(2, -2),
                //
                new Position(-2, -3),
                new Position(-1, -3),
                new Position(0, -3),
                new Position(1, -3),
                new Position(2, -3),
                //
                new Position(-2, -4),
                new Position(-1, -4),
                new Position(0, -4),
                new Position(1, -4),
                new Position(2, -4)
            )
        );
    }

    public function testGetPossiblePlacementsWithSuppliedOpponentBase()
    {
        $battlefield = new BattlefieldImpl(
            2,
            [
                new CardPlacement(new HillCard(), new Position(0, 0)),
                new CardPlacement(new InfantryCard(2), new Position(0, -1)),
                new CardPlacement(new InfantryCard(2), new Position(-1, -1)),
                new CardPlacement(new InfantryCard(2), new Position(-1, 0)),
                new CardPlacement(new InfantryCard(2), new Position(-1, 1))
            ]
        );
        $card = new ParatroopersCard(2);

        assertThat(
            $card->getPossiblePlacementPositions($battlefield),
            hasValue(
                new Position(0, 1)
            )
        );
    }
}
