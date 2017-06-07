<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use TheBattleForHill218\SQLHelper;

class GetGameProgressionTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
    }

    public function testAtStart()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();

        assertThat($game->getGameProgression(), identicalTo(0));
    }

    public function testAtEnd()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();
        $this->table->getDbConnection()->exec('DELETE FROM deck_card WHERE 1');
        $this->table->getDbConnection()->exec('DELETE FROM playable_card WHERE 1');

        assertThat($game->getGameProgression(), identicalTo(100));
    }

    public function testAtMidPoint()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();
        $this->table->getDbConnection()->exec('DELETE FROM deck_card WHERE player_id = 66 LIMIT 13');
        $this->table->getDbConnection()->exec('DELETE FROM deck_card WHERE player_id = 77 LIMIT 13');

        assertThat($game->getGameProgression(), identicalTo(50));
    }
}
