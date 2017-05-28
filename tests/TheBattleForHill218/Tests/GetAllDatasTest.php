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
                'players' => nonEmptyArray(),
                'me' => M::hasEntries([
                    'hand' => arrayWithSize(7),
                    'deckSize' => 19
                ]),
                'opponent' => M::hasEntries([
                    'handSize' => 7,
                    'deckSize' => 19,
                    'numAirStrikes' => 2
                ]),
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