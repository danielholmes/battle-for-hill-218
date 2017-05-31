<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;
use Functional as F;

class ArgPlayCardTest extends ProjectIntegrationTestCase
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

    public function testArgPlayCardForActive()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66);

        $datas = $game->argPlayCard();

        $handCardIds = F\pluck($this->table->fetchDbRows('playable_card', ['player_id' => 66]), 'id');
        assertThat($datas, allOf(M::hasKeys($handCardIds), everyItem(arrayValue())));
    }

    public function testArgPlayCardForNotActive()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(77);

        $datas = $game->argPlayCard();

        assertThat($datas, emptyArray());
    }
}