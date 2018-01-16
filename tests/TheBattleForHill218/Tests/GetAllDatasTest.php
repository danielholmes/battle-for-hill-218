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
                        'numDefeatedCards' => 0,
                        'numCards' => 7,
                        'deckSize' => 19,
                        'numUnitsInPlay' => 0,
                        'scoreAux' => 0,
                        'number' => anyOf(1, 2)
                    ]),
                    allOf(
                        M\hasEntries([
                            'id' => 77,
                            'numDefeatedCards' => 0,
                            'numCards' => 7,
                            'deckSize' => 19,
                            'numUnitsInPlay' => 0,
                            'scoreAux' => 0,
                            'number' => anyOf(1, 2)
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
                $db->exec('INSERT INTO battlefield_card (player_id, type, x, y) VALUES (66, "tank", 0, 2)');
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
                        'numDefeatedCards' => 0,
                        'numCards' => 0,
                        'deckSize' => 0,
                        'numUnitsInPlay' => 1,
                        'number' => anyOf(1, 2)
                    ]),
                    allOf(
                        M\hasEntries([
                            'id' => 77,
                            'numDefeatedCards' => 0,
                            'numCards' => 0,
                            'deckSize' => 0,
                            'numUnitsInPlay' => 0,
                            'number' => anyOf(1, 2)
                        ]),
                        not(hasKey('cards'))
                    )
                ),
                'battlefield' => containsInAnyOrder(
                    M\hasEntries([
                        'playerId' => null,
                        'playerColor' => null,
                        'type' => 'hill',
                        'x' => 0,
                        'y' => 0
                    ]),
                    M\hasEntries([
                        'playerId' => 66,
                        'playerColor' => nonEmptyString(),
                        'type' => 'tank',
                        'x' => 0,
                        'y' => 2
                    ])
                )
            ])
        );
    }
}
