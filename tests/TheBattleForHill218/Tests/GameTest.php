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
            ->setPlayers(array(
                66 => array(),
                77 => array()
            ))
            ->build();
        $this->gameInstance->createDatabase();
    }

    protected function tearDown()
    {
        $this->gameInstance->dropDatabase();
    }

    public function testInit()
    {
        $game = $this->gameInstance->setupNewGame();
    }
}