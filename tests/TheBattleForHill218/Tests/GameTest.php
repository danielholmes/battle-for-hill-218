<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\GameTableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;

class GameTest extends ProjectIntegrationTestCase
{
    /**
     * @var GameTableInstance
     */
    private $gameInstance;

    protected function setUp()
    {
        $this->gameInstance = self::gameTableInstanceBuilder()
            ->setRandomPlayers(2)
            ->build()
            ->createDatabase();
    }

    protected function tearDown()
    {
        if ($this->gameInstance !== null) {
            $this->gameInstance->dropDatabase();
        }
    }

    public function testInit()
    {
        $game = $this->gameInstance->setupNewGame();
    }
}