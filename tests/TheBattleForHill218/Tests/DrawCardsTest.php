<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;

class DrawCardsTest extends TestCase
{
    use TestHelp;

    /**
     * @inheritdoc
     */
    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayers([
                ['player_id' => 66, 'player_no' => 1],
                ['player_id' => 77, 'player_no' => 2]
            ]);
    }

    public function testDrawCardsOnFirstTurn()
    {
        /** @var \BattleForHill $game */
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();

        $game->stubCurrentPlayerId(66)->returnToDeck([3, 4]);
        $game->stubCurrentPlayerId(77)->returnToDeck([10, 11]);
        $game->resetNotifications();

        $game->stubActivePlayerId(66)->stDrawCards();

        $lastInsertedPlayableId = $this->table->fetchValue(
            'SELECT id FROM playable_card WHERE player_id = 66 ORDER BY id DESC LIMIT 1'
        );
        assertThat(
            $this->table->fetchValue('SELECT turn_plays_remaining FROM player WHERE player_id = 66'),
            equalTo(1)
        );
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 66]), arrayWithSize(20));
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 77]), arrayWithSize(21));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(6));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(5));
        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 66,
                    'type' => 'myCardsDrawn',
                    'log' => '',
                    'args' => hasEntry('cards', contains(hasEntry('id', $lastInsertedPlayableId)))
                ]),
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'cardsDrawn',
                    'log' => '${playerName} has drawn ${numCards} card',
                    'args' => M\hasEntries([
                        'numCards' => 1,
                        'handCount' => 4,
                        'deckCount' => 20,
                        'playerId' => 66
                    ])
                ])
            )
        );
    }

    public function testDrawCardsOnSecondTurn()
    {
        /** @var \BattleForHill $game */
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();

        $game->stubCurrentPlayerId(66)->returnToDeck([3, 4]);
        $game->stubCurrentPlayerId(77)->returnToDeck([10, 11]);
        $game->resetNotifications();

        $game->stubActivePlayerId(77)->stDrawCards();

        assertThat(
            $this->table->fetchValue('SELECT turn_plays_remaining FROM player WHERE player_id = 66'),
            equalTo(2)
        );
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 66]), arrayWithSize(21));
        assertThat($this->table->fetchDbRows('deck_card', ['player_id' => 77]), arrayWithSize(19));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(5));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(7));
        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 77,
                    'type' => 'myCardsDrawn',
                    'log' => '',
                    'args' => hasEntry('cards', arrayWithSize(2))
                ]),
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'cardsDrawn',
                    'log' => '${playerName} has drawn ${numCards} cards',
                    'args' => M\hasEntries([
                        'numCards' => 2,
                        'handCount' => 5,
                        'deckCount' => 19
                    ])
                ])
            )
        );
    }

    public function testDrawCardsWhenNoDeck()
    {
        $game = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->exec('DELETE FROM deck_card WHERE 1');
            })
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);

        $game->stDrawCards();

        assertThat($this->table->fetchDbRows('deck_card'), emptyArray());
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(7));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(7));
        assertThat($game->getNotifications(), emptyArray());
    }

    // Case similar to start where only draw one card - make sure player gets 2 turns
    public function testDrawSingleLastCardFromDeck()
    {
        /** @var \BattleForHill $game */
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();
        $game->stubCurrentPlayerId(66)->returnToDeck([3, 4]);
        $game->stubCurrentPlayerId(77)->returnToDeck([10, 11]);
        $game->resetNotifications();
        $this->table->withDbConnection(function (Connection $db) {
            $oneOf77sCardIds = $db->executeQuery('SELECT id FROM deck_card WHERE player_id = 77 LIMIT 1')
                ->fetchColumn();
            $db->exec("DELETE FROM deck_card WHERE id != {$oneOf77sCardIds}");
            $db->exec('UPDATE player SET turn_plays_remaining = 0');
        });

        $game->stubActivePlayerId(77)->stDrawCards();

        assertThat(
            $this->table->fetchValue('SELECT turn_plays_remaining FROM player WHERE player_id = 77'),
            equalTo(2)
        );
        assertThat($this->table->fetchDbRows('deck_card'), emptyArray());
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(5));
        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 77]), arrayWithSize(6));
        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 77,
                    'type' => 'myCardsDrawn',
                    'log' => '',
                    'args' => hasEntry('cards', arrayWithSize(1))
                ]),
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'cardsDrawn',
                    'log' => '${playerName} has drawn ${numCards} card',
                    'args' => M\hasEntries([
                        'numCards' => 1,
                        'handCount' => 4,
                        'deckCount' => 0
                    ])
                ])
            )
        );
    }
}
