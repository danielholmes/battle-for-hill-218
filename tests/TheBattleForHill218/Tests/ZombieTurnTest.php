<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use TheBattleForHill218\SQLHelper;

class ZombieTurnTest extends TestCase
{
    use TestHelp;

    /**
     * @inheritdoc
     */
    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayers([
                ['player_id' => 66, 'player_no' => 1],
                ['player_id' => 77, 'player_no' => 2]
            ])
            ->overridePlayersPostSetup([
                66 => ['player_color' => '000000'],
                77 => ['player_color' => \BattleForHillDhau::DOWNWARD_PLAYER_COLOR]
            ]);
    }

    public function testReturnToDeck()
    {
        $this->markTestSkipped('TODO: Check how works with multiactive in terms of activePlayerId');
        $this->table
            ->setupNewGame()
            ->runZombieTurn('returnToDeck');
    }

    public function testPlayCard()
    {
        $this->table
            ->setupNewGame()
            ->runZombieTurn('playCard', 66);

        assertThat($this->table->fetchDbRows('playable_card', ['player_id' => 66]), arrayWithSize(6));
    }

    public function testChooseAttack()
    {
        $this->table->setupNewGame();
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
        $this->table->getDbConnection()->update('player', ['player_score' => 2], ['player_id' => 66]);
        $this->table->getDbConnection()->update('player', ['player_score' => 1], ['player_id' => 77]);

        $game = $this->table->runZombieTurn('chooseAttack', 66);

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
            $game->getNotifications(),
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
}