<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Utils;

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
            ->build()
            ->createDatabase();
    }

    protected function tearDown()
    {
        if ($this->table !== null) {
            $this->table->dropDatabaseAndDisconnect();
        }
    }

    public function testGetAllDatas()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);

        $datas = Utils::callProtectedMethod($game, 'getAllDatas');

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
                        'playerColor' => null,
                        'type' => 'hill',
                        'x' => 0,
                        'y' => 0
                    ])
                )
            ])
        );
    }
}