<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use TheBattleForHill218\SQLHelper;

class ChooseAttackTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77])
            ->overridePlayersPostSetup([
                66 => ['player_color' => '000000'],
                77 => ['player_color' => \BattleForHillDhau::DOWNWARD_PLAYER_COLOR]
            ]);
    }

    public function testArgChooseAttack()
    {
        $game = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->exec(SQLHelper::insertAll(
                    'battlefield_card',
                    [
                        [
                            'player_id' => 77,
                            'type' => 'infantry',
                            'x' => 0,
                            'y' => -1
                        ],
                        [
                            'player_id' => 66,
                            'type' => 'infantry',
                            'x' => 0,
                            'y' => 1
                        ],
                        [
                            'player_id' => 66,
                            'type' => 'artillery',
                            'x' => 1,
                            'y' => 1
                        ]
                    ]
                ));
            })
            ->createGameInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66);

        $datas = $game->argChooseAttack();

        assertThat(
            $datas['_private']['active'],
            contains(
                M\hasEntries([
                    'x' => 0,
                    'y' => -1
                ])
            )
        );
    }

    public function testChooseAttackValid()
    {
        $action = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->exec(SQLHelper::insertAll(
                    'battlefield_card',
                    [
                        [
                            'player_id' => 77,
                            'type' => 'infantry',
                            'x' => 0,
                            'y' => -1
                        ],
                        [
                            'player_id' => 66,
                            'type' => 'infantry',
                            'x' => 0,
                            'y' => 1
                        ],
                        [
                            'player_id' => 66,
                            'type' => 'artillery',
                            'x' => 1,
                            'y' => 1
                        ]
                    ]
                ));
                $db->update('player', ['player_score' => 2], ['player_id' => 66]);
                $db->update('player', ['player_score' => 1], ['player_id' => 77]);
            })
            ->createActionInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66);

        $action->stubArgs(['x' => 0, 'y' => -1])->chooseAttack();

        assertThat(
            $this->table->fetchDbRows('battlefield_card'),
            allOf(
                not(hasItem(M\hasEntries([
                    'x' => 0,
                    'y' => -1
                ]))),
                arrayWithSize(3)
            )
        );
        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder(
                M\hasEntries(['player_id' => 66, 'player_score' => 2]),
                M\hasEntries(['player_id' => 77, 'player_score' => 0])
            )
        );
        assertThat(
            $action->getGame()->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'cardAttacked',
                    'log' => '${playerName} attacked ${x},${y}',
                    'args' => M\hasEntries([
                        'playerName' => nonEmptyString(),
                        'x' => 0,
                        'y' => -1,
                        'fromX' => 1,
                        'fromY' => 1
                    ])
                ]),
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'newScores',
                    'log' => '',
                    'args' => [66 => 2, 77 => 0]
                ])
            )
        );
    }

    public function testChooseAttackInvalid()
    {
        $this->expectException('BgaUserException');

        $action = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->exec(SQLHelper::insertAll(
                    'battlefield_card',
                    [
                        [
                            'player_id' => 77,
                            'type' => 'infantry',
                            'x' => 0,
                            'y' => -1
                        ],
                        [
                            'player_id' => 66,
                            'type' => 'infantry',
                            'x' => 0,
                            'y' => 1
                        ],
                        [
                            'player_id' => 66,
                            'type' => 'artillery',
                            'x' => 1,
                            'y' => 1
                        ]
                    ]
                ));
            })
            ->createActionInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66)
            ->stubArgs(['x' => 5, 'y' => 5]);

        $action->chooseAttack();
    }
}
