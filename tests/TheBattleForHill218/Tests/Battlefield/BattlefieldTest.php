<?php

namespace TheBattleForHill218\Tests\Battlefield;

use PHPUnit\Framework\TestCase;
use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\CardPlacement;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Cards\ArtilleryCard;
use TheBattleForHill218\Cards\HeavyWeaponsCard;
use TheBattleForHill218\Cards\HillCard;
use TheBattleForHill218\Cards\InfantryCard;
use TheBattleForHill218\Cards\ParatroopersCard;
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
    private $sampleBattlefield;

    protected function setUp()
    {
        $this->emptyBattlefield = $this->sampleBattlefield = new Battlefield(
            2,
            [new CardPlacement(new HillCard(), new Position(0, 0))]
        );
        $this->sampleBattlefield = new Battlefield(
            2,
            [
                new CardPlacement(new HillCard(), new Position(0, 0)),
                new CardPlacement(new ParatroopersCard(1), new Position(0, 1)),
                new CardPlacement(new InfantryCard(2), new Position(0, 2)),
                new CardPlacement(new TankCard(1), new Position(0, 3))
            ]
        );
    }

    public function testConstructWithOverlapping()
    {
        $this->expectException('InvalidArgumentException');

        new Battlefield(2, [
            new CardPlacement(new InfantryCard(1), new Position(1, 0)),
            new CardPlacement(new HeavyWeaponsCard(1), new Position(1, 0))
        ]);
    }

    public function testGetPositionsOfOpponent()
    {
        assertThat(
            $this->sampleBattlefield->getPositionsOfOpponent(2),
            containsInAnyOrder(
                new Position(0, 1),
                new Position(0, 3)
            )
        );
    }

    public function testGetAllowedPositionsSimple()
    {
        $positions = $this->sampleBattlefield->getAllowedPositions(
            1,
            [new SupplyOffset(0, 1), new SupplyOffset(1, 0)]
        );

        assertThat($positions, containsInAnyOrder(new Position(-1, 1)));
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

    public function testGetAllowedPositionsDoesntUseSupplyFromDisconnected()
    {
        $battlefield = new Battlefield(2, [
            new CardPlacement(new HillCard(), new Position(0, 0)),
            // Supplied
            new CardPlacement(new InfantryCard(1), new Position(0, 1)),
            new CardPlacement(new ArtilleryCard(1), new Position(1, 1)),
            new CardPlacement(new ArtilleryCard(1), new Position(2, 1)),

            // Single cut off from base
            new CardPlacement(new ParatroopersCard(1), new Position(5, 5)),

            // Supply each other, but cut off from base
            new CardPlacement(new InfantryCard(1), new Position(-2, 1)),
            new CardPlacement(new HeavyWeaponsCard(1), new Position(-3, 1)),

            // Opponent player (but would be supplied if same
            new CardPlacement(new ArtilleryCard(2), new Position(-1, 1))
        ]);

        $positions = $battlefield->getAllowedPositions(1, [new SupplyOffset(0, -1)]);

        assertThat(
            $positions,
            containsInAnyOrder([new Position(0, 2), new Position(1, 2), new Position(2, 2)])
        );
    }

    public function testGetAttackablePlacementsSingleFound()
    {
        $opponentInfantry = new CardPlacement(new InfantryCard(2), new Position(0, -1));
        $tank = new CardPlacement(new TankCard(1), new Position(1, -1));
        $battlefield = new Battlefield(2, [
            new CardPlacement(new HillCard(), new Position(0, 0)),
            $opponentInfantry,
            $tank
        ]);

        assertThat($battlefield->getAttackablePlacements($tank), contains($opponentInfantry));
    }

    public function testGetAttackablePlacementsSingleFoundButNoSupport()
    {
        $opponentInfantry = new CardPlacement(new InfantryCard(2), new Position(0, -1));
        $infantry = new CardPlacement(new InfantryCard(1), new Position(1, -1));
        $battlefield = new Battlefield(2, [
            new CardPlacement(new HillCard(), new Position(0, 0)),
            $opponentInfantry,
            $infantry
        ]);

        assertThat($battlefield->getAttackablePlacements($infantry), emptyArray());
    }

    public function testGetAttackablePlacementsCantAttack()
    {
        $tank = new CardPlacement(new TankCard(1), new Position(1, -1));
        $battlefield = new Battlefield(2, [
            new CardPlacement(new HillCard(), new Position(0, 0)),
            new CardPlacement(new InfantryCard(2), new Position(10, 10)),
            $tank
        ]);

        assertThat($battlefield->getAttackablePlacements($tank), emptyArray());
    }

    public function testGetAttackablePlacementsWithSupport()
    {
        $opponent1 = new CardPlacement(new InfantryCard(2), new Position(-1, -2));
        $opponent2 = new CardPlacement(new InfantryCard(2), new Position(0, -1));
        $opponentMissing = new CardPlacement(new InfantryCard(2), new Position(-2, -1));

        $supporter = new CardPlacement(new HeavyWeaponsCard(1), new Position(-1, -1));
        $attacker = new CardPlacement(new InfantryCard(1), new Position(0, -2));

        $battlefield = new Battlefield(2, [
            new CardPlacement(new HillCard(), new Position(0, 0)),
            $opponent1,
            $opponent2,
            $opponentMissing,
            $attacker,
            $supporter
        ]);

        assertThat($battlefield->getAttackablePlacements($attacker), containsInAnyOrder($opponent1, $opponent2));
    }

    public function testGetAttackablePlacementsNotPresent()
    {
        $this->expectException('InvalidArgumentException');

        $battlefield = new Battlefield(2, [
            new CardPlacement(new HillCard(), new Position(0, 0)),
            new CardPlacement(new InfantryCard(2), new Position(0, -1))
        ]);

        $battlefield->getAttackablePlacements(new CardPlacement(new TankCard(1), new Position(1, -1)));
    }
}
