<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use BGAWorkbench\Test\TestHelp;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class NextPlayTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77])
            ->overridePlayersPostSetup([
                66 => ['player_no' => 1],
                77 => ['player_no' => 2]
            ]);
    }

    /**
     * @param callable $callable
     * @return \BattleForHill
     */
    private function createGameReadyForNext($callable)
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();

        $db = $this->table->getDbConnection();
        TestUtils::return2RandomCards($game, $db, 66);
        TestUtils::return2RandomCards($game, $db,77);
        $game->stubActivePlayerId(77)->stDrawCards();
        call_user_func($callable, $db);
        return $game;
    }

    public function testNextPlaySwitchPlayer()
    {
        $this->createGameReadyForNext(function (Connection $db) {
            $db->exec('UPDATE player SET turn_plays_remaining = 1 WHERE player_id = 66');
            $db->exec('UPDATE player SET turn_plays_remaining = 0 WHERE player_id = 77');
        })->stubActivePlayerId(66)
            ->stNextPlay();

        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder([
                M\hasEntries(['player_id' => 66, 'turn_plays_remaining' => 0]),
                M\hasEntries(['player_id' => 77, 'turn_plays_remaining' => 2])
            ])
        );
    }

    public function testNextPlaySwitchPlayerNoCardsLeftForOnePlayer()
    {
        $this->createGameReadyForNext(function (Connection $db) {
            $db->exec('UPDATE player SET turn_plays_remaining = 2 WHERE player_id = 66');
            $db->exec('DELETE FROM playable_card WHERE player_id = 66');
            $db->exec('UPDATE player SET turn_plays_remaining = 0 WHERE player_id = 77');
        })->stubActivePlayerId(66)
            ->stNextPlay();

        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder([
                M\hasEntries(['player_id' => 66, 'turn_plays_remaining' => 1]),
                M\hasEntries(['player_id' => 77, 'turn_plays_remaining' => 2])
            ])
        );
    }

    public function testNextPlaySamePlayer()
    {
        $this->createGameReadyForNext(function (Connection $db) {
            $db->exec('UPDATE player SET turn_plays_remaining = 2 WHERE player_id = 66');
            $db->exec('UPDATE player SET turn_plays_remaining = 0 WHERE player_id = 77');
        })->stubActivePlayerId(66)
            ->stNextPlay();

        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder([
                M\hasEntries(['player_id' => 66, 'turn_plays_remaining' => 1]),
                M\hasEntries(['player_id' => 77, 'turn_plays_remaining' => 0])
            ])
        );
    }

    public function testNextPlayNoCardsLeft()
    {
        $game = $this->createGameReadyForNext(function (Connection $db) {
            $db->exec('DELETE FROM deck_card WHERE 1');
            $db->exec('DELETE FROM playable_card WHERE 1');
        })->stubActivePlayerId(66);
        $game->resetNotifications();

        $game->stNextPlay();

        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'endOfGame',
                    'log' => ''
                ])
            )
        );
    }

    public function testNextPlayNoValidMovesSoSwitch()
    {
        $this->markTestSkipped('TODO');

        $this->createGameReadyForNext(function (Connection $db) {
            $db->exec('UPDATE player SET turn_plays_remaining = 2 WHERE player_id = 66');
            $db->exec('UPDATE player SET turn_plays_remaining = 0 WHERE player_id = 77');
            $db->exec('DELETE FROM playable_card WHERE player_id = 66');
            $db->exec('INSERT INTO playable_card (player_id, type, `order`) VALUES (66, "air-strike", 0)');
            $db->exec('INSERT INTO playable_card (player_id, type, `order`) VALUES (66, "air-strike", 1)');
        })->stubActivePlayerId(66)
            ->stNextPlay();

        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder([
                M\hasEntries(['player_id' => 66, 'turn_plays_remaining' => 0]),
                M\hasEntries(['player_id' => 77, 'turn_plays_remaining' => 2])
            ])
        );
    }
}
