<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;

class ReturnToDeckTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
    }

    public function testReturnToDeck()
    {
        $action = $this->table
            ->setupNewGame()
            ->createActionInstanceForCurrentPlayer(66)
            ->stubArg('ids', '3,4');

        $action->returnToDeck([3, 4]);

        assertThat(
            $this->table->fetchDbRows('deck_card', ['player_id' => 66]),
            arrayWithSize(21)
        );
        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            allOf(
                arrayWithSize(5),
                not(hasItem(hasEntry('id', 3))),
                not(hasItem(hasEntry('id', 4)))
            )
        );

        assertThat(
            $action->getGame()->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'returnedToDeck',
                    'args' => M\hasEntries([
                        'playerId' => 66,
                        'deckCount' => 21,
                        'handCount' => 3,
                        'numCards' => 2
                    ])
                ])
            )
        );
    }
}
