<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use BGAWorkbench\Test\TestHelp;
use PHPUnit\Framework\TestCase;

class NextPlayTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayers([
                ['player_id' => 66, 'player_no' => 1],
                ['player_id' => 77, 'player_no' => 2]
            ]);
    }

    /**
     * @return \BattleForHillDhau
     */
    private function createGameReadyForNext()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();

        $game->stubCurrentPlayerId(66)->returnToDeck([3, 4]);
        $game->stubCurrentPlayerId(77)->returnToDeck([10, 11]);
        $game->stubActivePlayerId(77)->stDrawCards();
        return $game;
    }

    public function testNextPlaySwitchPlayer()
    {
        $game = $this->createGameReadyForNext();
        $this->table->getDbConnection()
            ->exec('UPDATE player SET turn_plays_remaining = 1 WHERE player_id = 66');
        $this->table->getDbConnection()
            ->exec('UPDATE player SET turn_plays_remaining = 0 WHERE player_id = 77');

        $game->stubActivePlayerId(66)->stNextPlay();

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
        $game = $this->createGameReadyForNext();
        $this->table->getDbConnection()
            ->exec('UPDATE player SET turn_plays_remaining = 2 WHERE player_id = 66');
        $this->table->getDbConnection()->exec('DELETE FROM playable_card WHERE player_id = 66');
        $this->table->getDbConnection()
            ->exec('UPDATE player SET turn_plays_remaining = 0 WHERE player_id = 77');

        $game->stubActivePlayerId(66)->stNextPlay();

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
        $game = $this->createGameReadyForNext();
        $this->table->getDbConnection()
            ->exec('UPDATE player SET turn_plays_remaining = 2 WHERE player_id = 66');
        $this->table->getDbConnection()
            ->exec('UPDATE player SET turn_plays_remaining = 0 WHERE player_id = 77');

        $game->stubActivePlayerId(66)->stNextPlay();

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
        $game = $this->createGameReadyForNext();

        $this->table->getDbConnection()->exec('DELETE FROM deck_card WHERE 1');
        $this->table->getDbConnection()->exec('DELETE FROM playable_card WHERE 1');

        $game->stubActivePlayerId(66)->stNextPlay();
    }
}
