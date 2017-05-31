<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;

class DrawCardsTest extends ProjectIntegrationTestCase
{
    /**
     * @var TableInstance
     */
    private $table;

    protected function setUp()
    {
        $this->table = self::gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77])
            ->build()
            ->createDatabase();
    }

    protected function tearDown()
    {
        if ($this->table !== null) {
            $this->table->dropDatabaseAndDisconnect();
        }
    }

    public function testDrawCardsOnFirstTurn()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);

        $game->stDrawCards();

        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 66]), arrayWithSize(18));
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 77]), arrayWithSize(19));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(8));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(7));
        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M::hasEntries([
                    'playerId' => 66,
                    'type' => 'myCardsDrawn',
                    'log' => '',
                    'args' => hasEntry('cards', contains(nonEmptyArray()))
                ]),
                M::hasEntries([
                    'playerId' => 'all',
                    'type' => 'cardsDrawn',
                    'log' => '${playerName} has drawn ${numCards} card',
                    'args' => M::hasEntries([
                        'numCards' => 1,
                        'playerId' => 66
                    ])
                ])
            )
        );
    }

    public function testDrawCardsOnSecondTurn()
    {
        $this->markTestSkipped('Not yet implemented');

        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);

        $game->stDrawCards();

        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 66]), arrayWithSize(18));
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 77]), arrayWithSize(19));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(8));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(7));
        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M::hasEntries([
                    'playerId' => 66,
                    'type' => 'myCardsDrawn',
                    'log' => '',
                    'args' => hasEntry('cards', contains(nonEmptyArray()))
                ]),
                M::hasEntries([
                    'playerId' => 'all',
                    'type' => 'cardsDrawn',
                    'log' => '${playerName} has drawn ${numCards} card',
                    'args' => hasEntry('numCards', 1)
                ])
            )
        );
    }

    public function testDrawCardsWhenNoDeck()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);
        $this->table->getDbConnection()->exec('DELETE FROM deck_card WHERE 1');

        $game->stDrawCards();

        assertThat($this->table->fetchDbRows('deck_card'), emptyArray());
        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            arrayWithSize(7)
        );
        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 77]),
            arrayWithSize(7)
        );
        assertThat($game->getNotifications(), emptyArray());
    }
}