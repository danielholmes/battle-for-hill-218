<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\ProjectIntegrationTestCase;

class GameTest extends ProjectIntegrationTestCase
{
    public function testInit()
    {
        $table = self::gameTableInstanceBuilder()
            ->setPlayers(array(
                66 => array(),
                77 => array()
            ))
            ->build()
            ->setupNewGame();
    }
}