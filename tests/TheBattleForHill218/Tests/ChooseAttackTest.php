<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use TheBattleForHill218\SQLHelper;

class ChooseAttackTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
    }

    public function testArgChooseAttack()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66);
        $this->table->getDbConnection()->exec(SQLHelper::insertAll(
            'battlefield_card', [
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
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66);
        $this->table->getDbConnection()->exec(SQLHelper::insertAll(
            'battlefield_card', [
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

        $game->chooseAttack(0, -1);

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
            $game->getNotifications(),
            contains(M\hasEntries([
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
            ]))
        );
    }

    public function testChooseAttackInvalid()
    {
        $this->expectException('BgaUserException');

        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66);
        $this->table->getDbConnection()->exec(SQLHelper::insertAll(
            'battlefield_card', [
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

        $game->chooseAttack(5, 5);
    }
}
