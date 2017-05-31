<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use Functional as F;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;

class ArgPlayCardTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
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