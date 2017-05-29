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
            ->buildForCurrentPlayer(66)
            ->createDatabase();
    }

    protected function tearDown()
    {
        if ($this->table !== null) {
            $this->table->dropDatabase();
        }
    }

    public function testArgPlayCardForActive()
    {
        $datas = $this->table
            ->setupNewGame()
            ->setActivePlayer(66)
            ->getTable()
            ->argPlayCard();

        $handCardIds = F\pluck($this->table->fetchDbRows('playable_card', ['player_id' => 66]), 'id');
        assertThat($datas, allOf(M::hasKeys($handCardIds), everyItem(contains(M::hasEntries(['x' => 0, 'y' => 1])))));
    }

    public function testArgPlayCardForNotActive()
    {
        $datas = $this->table
            ->setupNewGame()
            ->setActivePlayer(77)
            ->getTable()
            ->argPlayCard();

        assertThat($datas, emptyArray());
    }
}