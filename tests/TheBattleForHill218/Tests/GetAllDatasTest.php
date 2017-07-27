<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use BGAWorkbench\Utils;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;

class GetAllDatasTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
    }

    public function testInitialCase()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);

        $datas = Utils::callProtectedMethod($game, 'getAllDatas');

        assertThat(
            $datas,
            M\hasEntries([
                'players' => containsInAnyOrder(
                    M\hasEntries([
                        'id' => 66,
                        'cards' => arrayWithSize(7),
                        'numCards' => 7,
                        'numAirStrikes' => 2,
                        'deckSize' => 19
                    ]),
                    allOf(
                        M\hasEntries([
                            'id' => 77,
                            'numCards' => 7,
                            'numAirStrikes' => 2,
                            'deckSize' => 19
                        ]),
                        not(hasKey('cards'))
                    )
                ),
                'battlefield' => contains(
                    M\hasEntries([
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

    public function testEndCase()
    {
        $game = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->exec('DELETE FROM deck_card');
                $db->exec('DELETE FROM playable_card');
            })
            ->createGameInstanceForCurrentPlayer(66);

        $datas = Utils::callProtectedMethod($game, 'getAllDatas');

        assertThat(
            $datas,
            M\hasEntries([
                'players' => containsInAnyOrder(
                    M\hasEntries([
                        'id' => 66,
                        'cards' => emptyArray(),
                        'numCards' => 0,
                        'numAirStrikes' => 0,
                        'deckSize' => 0
                    ]),
                    allOf(
                        M\hasEntries([
                            'id' => 77,
                            'numCards' => 0,
                            'numAirStrikes' => 0,
                            'deckSize' => 0
                        ]),
                        not(hasKey('cards'))
                    )
                ),
                'battlefield' => contains(
                    M\hasEntries([
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
