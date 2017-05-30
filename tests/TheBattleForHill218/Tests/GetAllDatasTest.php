<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;

class GetAllDatasTest extends ProjectIntegrationTestCase
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

    public function testGetAllDatas()
    {
        $datas = $this->table
            ->setupNewGame()
            ->callProtectedAndReturn('getAllDatas');

        assertThat(
            $datas,
            M::hasEntries([
                'players' => containsInAnyOrder(
                    M::hasEntries([
                        'id' => 66,
                        'cards' => arrayWithSize(7),
                        'numCards' => 7,
                        'numAirStrikes' => 2,
                        'deckSize' => 19
                    ]),
                    allOf(
                        M::hasEntries([
                            'id' => 77,
                            'numCards' => 7,
                            'numAirStrikes' => 2,
                            'deckSize' => 19
                        ]),
                        not(hasKey('cards'))
                    )
                ),
                'battlefield' => contains(
                    M::hasEntries([
                        'playerId' => null,
                        'type' => 'hill',
                        'x' => 0,
                        'y' => 0
                    ])
                )
            ])
        );
    }
}