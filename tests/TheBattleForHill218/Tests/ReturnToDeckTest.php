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
            ->createActionInstanceForCurrentPlayer(66);
        $returnIds = TestUtils::get2RandomReturnableIds($this->table->getDbConnection(), 66);

        $action->stubArg('ids', implode(',', $returnIds))->returnToDeck();

        assertThat(
            $this->table->fetchDbRows('deck_card', ['player_id' => 66]),
            arrayWithSize(21)
        );
        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            allOf(
                arrayWithSize(5),
                not(hasItem(hasEntry('id', $returnIds[0]))),
                not(hasItem(hasEntry('id', $returnIds[1])))
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
                ]),
                M\hasEntries([
                    'playerId' => 66,
                    'type' => 'iReturnedToDeck',
                    'args' => M\hasEntries([
                        'cardIds' => $returnIds
                    ])
                ])
            )
        );
    }

    public function testReturnToDeckAirStrikesIsInvalid()
    {
        $this->expectException('BgaUserException');

        $action = $this->table
            ->setupNewGame()
            ->createActionInstanceForCurrentPlayer(66)
            ->stubArg('ids', '1,2');

        $action->returnToDeck();
    }
}
