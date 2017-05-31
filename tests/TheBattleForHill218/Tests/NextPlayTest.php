<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TestHelp;
use PHPUnit\Framework\TestCase;

class NextPlayTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
    }

    // TODO: Play again/another play for current player

    public function testNextPlaySwitchPlayer()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);

        $game->stNextPlay();
    }

    public function testNextPlayNoCardsLeft()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);
        $this->table->getDbConnection()->exec('DELETE FROM deck_card WHERE 1');
        $this->table->getDbConnection()->exec('DELETE FROM playable_card WHERE 1');

        $game->stNextPlay();
    }
}