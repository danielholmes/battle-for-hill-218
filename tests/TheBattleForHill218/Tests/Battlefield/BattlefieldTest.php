<?php

namespace TheBattleForHill218\Tests\Battlefield;

use PHPUnit\Framework\TestCase;
use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\CardPlacement;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Cards\HillCard;
use TheBattleForHill218\Cards\InfantryCard;
use TheBattleForHill218\Cards\ParatrooperCard;
use TheBattleForHill218\Cards\SupplyOffset;
use TheBattleForHill218\Cards\TankCard;

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
                new CardPlacement(new ParatrooperCard(1), new Position(0, 1)),
                new CardPlacement(new InfantryCard(2), new Position(0, 2)),
                new CardPlacement(new TankCard(1), new Position(0, 3))
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
            $this->emptyBattlefield->getAllowedPositions(2, SupplyOffset::createPlusConfig()),
            contains(new Position(0, -1))
        );
    }

    public function testGetAllowedPositionsWithNoBaseUpwards()
    {
        assertThat(
            $this->emptyBattlefield->getAllowedPositions(3, SupplyOffset::createPlusConfig()),
            contains(new Position(0, 1))
        );
    }
}