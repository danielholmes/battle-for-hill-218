<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use TheBattleForHill218\SQLHelper;

class GetGameProgressionTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
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
            ->withDbConnection(function (Connection $db) {
                $db->exec('DELETE FROM deck_card WHERE 1');
                $db->exec('DELETE FROM playable_card WHERE 1');
            })
            ->createGameInstanceWithNoBoundedPlayer();

        assertThat($game->getGameProgression(), identicalTo(100));
    }

    public function testAtMidPoint()
    {
        $game = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->exec('DELETE FROM deck_card WHERE player_id = 66 LIMIT 13');
                $db->exec('DELETE FROM deck_card WHERE player_id = 77 LIMIT 13');
            })
            ->createGameInstanceWithNoBoundedPlayer();

        assertThat($game->getGameProgression(), identicalTo(50));
    }
}
