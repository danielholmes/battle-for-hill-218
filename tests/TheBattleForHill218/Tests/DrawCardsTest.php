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
            ->setPlayers([
                ['player_id' => 66, 'player_no' => 1],
                ['player_id' => 77, 'player_no' => 2]
            ]);
    }

    public function testDrawCardsOnFirstTurn()
    {
        /** @var \BattleForHillDhau $game */
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();

        $game->stubCurrentPlayerId(66)->returnToDeck([1, 2]);
        $game->stubCurrentPlayerId(77)->returnToDeck([8, 9]);
        $game->resetNotifications();

        $game->stubActivePlayerId(66)->stDrawCards();

        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 66]), arrayWithSize(20));
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 77]), arrayWithSize(21));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(6));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(5));
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
        /** @var \BattleForHillDhau $game */
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();

        $game->stubCurrentPlayerId(66)->returnToDeck([1, 2]);
        $game->stubCurrentPlayerId(77)->returnToDeck([8, 9]);
        $game->resetNotifications();

        $game->stubActivePlayerId(77)->stDrawCards();

        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 66]), arrayWithSize(21));
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 77]), arrayWithSize(19));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(5));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(7));
        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M::hasEntries([
                    'playerId' => 77,
                    'type' => 'myCardsDrawn',
                    'log' => '',
                    'args' => hasEntry('cards', arrayWithSize(2))
                ]),
                M::hasEntries([
                    'playerId' => 'all',
                    'type' => 'cardsDrawn',
                    'log' => '${playerName} has drawn ${numCards} cards',
                    'args' => hasEntry('numCards', 2)
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
