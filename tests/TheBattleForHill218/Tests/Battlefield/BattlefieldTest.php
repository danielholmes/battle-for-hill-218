<?php

namespace TheBattleForHill218\Tests\Battlefield;

use PHPUnit\Framework\TestCase;
use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\CardPlacement;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Cards\HillCard;
use TheBattleForHill218\Cards\InfantryBattlefieldCard;
use TheBattleForHill218\Cards\ParatrooperBattlefieldCard;
use TheBattleForHill218\Cards\SupplyOffset;
use TheBattleForHill218\Cards\TankBattlefieldCard;

class BattlefieldTest extends TestCase
{
    /**
     * @var Battlefield
     */
    private $emptyBattlefield;

    /**
     * @var Battlefield
     */
    private $battlefield;

    protected function setUp()
    {
        $this->emptyBattlefield = $this->battlefield = new Battlefield(
            2,
            [new CardPlacement(new HillCard(), new Position(0, 0))]
        );
        $this->battlefield = new Battlefield(
            2,
            [
                new CardPlacement(new HillCard(), new Position(0, 0)),
                new CardPlacement(new ParatrooperBattlefieldCard(1), new Position(0, 1)),
                new CardPlacement(new InfantryBattlefieldCard(2), new Position(0, 2)),
                new CardPlacement(new TankBattlefieldCard(1), new Position(0, 3))
            ]
        );
    }

    public function testGetPositionsOfOpponent()
    {
        assertThat(
            $this->battlefield->getPositionsOfOpponent(2),
            containsInAnyOrder(
                new Position(0, 1),
                new Position(0, 3)
            )
        );
    }

    public function testGetAllowedPositions()
    {
        assertThat(
            $this->battlefield->getAllowedPositions(
                1,
                [new SupplyOffset(0, 1), new SupplyOffset(1, 0)]
            ),
            containsInAnyOrder(
                new Position(-1, 1),
                new Position(-1, 3)
            )
        );
    }

    public function testGetAllowedPositionsWithNoBaseDownwards()
    {
        assertThat(
            $this->emptyBattlefield->getAllowedPositions(2, SupplyOffset::plusPattern()),
            contains(new Position(0, -1))
        );
    }

    public function testGetAllowedPositionsWithNoBaseUpwards()
    {
        assertThat(
            $this->emptyBattlefield->getAllowedPositions(3, SupplyOffset::plusPattern()),
            contains(new Position(0, 1))
        );
    }
}
