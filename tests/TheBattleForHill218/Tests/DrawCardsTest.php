<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;

class DrawCardsTest extends TestCase
{
    use TestHelp;

    /**
     * @inheritdoc
     */
    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
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
