<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;

class NextPlayTest extends ProjectIntegrationTestCase
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