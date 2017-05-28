<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;

class ReturnToDeckTest extends ProjectIntegrationTestCase
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

    public function testReturnToDeck()
    {
        $this->table
            ->setupNewGame()
            ->getTable()
            ->returnToDeck([3, 4]);

        assertThat(
            $this->table->getTrackedNotifications(),
            containsInAnyOrder(
                M::hasEntries([
                    'playerId' => 66,
                    'type' => 'returnedToDeck',
                    'args' => M::hasEntries([
                        'oldIds' => [3, 4],
                        'replacements' => arrayWithSize(2)
                    ])
                ]),
                M::hasEntries([
                    'playerId' => 77,
                    'type' => 'opponentReturnedToDeck',
                    'args' => hasEntry('numCards', 2)
                ])
            )
        );
    }
}