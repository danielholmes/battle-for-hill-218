<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;

class SetupNewGameTest extends ProjectIntegrationTestCase
{
    /**
     * @var TableInstance
     */
    private $table;

    protected function setUp()
    {
        $this->table = self::gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77])
            ->buildForCurrentPlayer(66)
            ->createDatabase();
    }

    protected function tearDown()
    {
        if ($this->table !== null) {
            $this->table->dropDatabase();
        }
    }

    public function testSetup()
    {
        $this->table->setupNewGame();

        $this->assertPlayersSetup();
        $this->assertBattlefieldSetup();
        $this->assertHandCardsSetup(66);
        $this->assertHandCardsSetup(77);
        $this->assertDeckCardsSetup(66);
        $this->assertDeckCardsSetup(77);
    }

    private function assertPlayersSetup()
    {
        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder([
                M::hasEntries(['player_color' => '6f0f11']),
                M::hasEntries(['player_color' => '04237b'])
            ])
        );
    }

    private function assertBattlefieldSetup()
    {
        assertThat(
            $this->table->fetchDbRows('battlefield_card'),
            containsInAnyOrder([
                M::hasEntries([
                    'player_id' => null,
                    'type' => 'hill',
                    'x' => 0,
                    'y' => 0
                ])
            ])
        );
    }

    /**
     * @param int $playerId
     */
    private function assertHandCardsSetup($playerId)
    {
        assertThat(
            $this->table->fetchDbRows('hand_card', ['player_id' => $playerId]),
            allOf(
                arrayWithSize(7),
                containsInAnyOrder([
                    M::hasEntries(['type' => 'air-strike', 'order' => 0]),
                    M::hasEntries(['type' => 'air-strike', 'order' => 1]),
                    M::hasEntries(['type' => not(equalTo('air-strike')), 'order' => 2]),
                    M::hasEntries(['type' => not(equalTo('air-strike')), 'order' => 3]),
                    M::hasEntries(['type' => not(equalTo('air-strike')), 'order' => 4]),
                    M::hasEntries(['type' => not(equalTo('air-strike')), 'order' => 5]),
                    M::hasEntries(['type' => not(equalTo('air-strike')), 'order' => 6])
                ])
            )
        );
    }

    /**
     * @param int $playerId
     */
    private function assertDeckCardsSetup($playerId)
    {
        assertThat(
            $this->table->fetchDbRows('deck_card', ['player_id' => $playerId]),
            allOf(
                arrayWithSize(19),
                everyItem(M::hasEntries(['type' => not(equalTo('air-strike'))]))
            )
        );
    }
}