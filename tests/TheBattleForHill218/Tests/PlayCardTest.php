<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use BGAWorkbench\Test\TableInstanceBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use Functional as F;
use TheBattleForHill218\SQLHelper;

class PlayCardTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77])
            ->overridePlayersPostSetup([
                66 => ['player_color' => '000000'],
                77 => ['player_color' => \BattleForHill::DOWNWARD_PLAYER_COLOR]
            ]);
    }

    public function testArgPlayCard()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66)
            ->stubActivePlayerId(66);

        $datas = $game->argPlayCard();

        $handCardIds = F\pluck($this->table->fetchDbRows('playable_card', ['player_id' => 66]), 'id');
        assertThat($datas['_private']['active'], allOf(M\hasKeys($handCardIds), everyItem(arrayValue())));
    }

    public function testPlayCardValid()
    {
        $action = $this->table
            ->setupNewGame()
            ->createActionInstanceForCurrentPlayer(66);
        $card = $this->getNonAirStrikePlayableCardForPlayer(66);

        $action->stubArgs([
            'id' => $card['id'],
            'x' => 0,
            'y' => 1
        ])->playCard();

        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            not(hasItem(hasEntry('id', $card['id'])))
        );
        assertThat(
            $this->table->fetchDbRows('battlefield_card', ['x' => 0, 'y' => 1]),
            contains(
                M\hasEntries([
                    'type' => $card['type'],
                    'player_id' => 66,
                    'x' => 0,
                    'y' => 1
                ])
            )
        );
        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder(
                M\hasEntries(['player_id' => 66, 'player_score_aux' => 0]),
                M\hasEntries(['player_id' => 77, 'player_score_aux' => 0])
            )
        );
        assertThat(
            $action->getGame()->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'placedCard',
                    'log' => '${playerName} placed a ${typeName} card at ${x},${y}',
                    'args' => M\hasEntries([
                        'playerId' => 66,
                        'playerName' => nonEmptyString(),
                        'typeName' => nonEmptyString(),
                        'typeKey' => nonEmptyString(),
                        'handCount' => 4,
                        'x' => 0,
                        'y' => 1
                    ])
                ]),
                M\hasEntries([
                    'playerId' => 66,
                    'type' => 'iPlacedCard',
                    'log' => '',
                    'args' => M\hasEntries([
                        'cardId' => $card['id'],
                        'x' => 0,
                        'y' => 1
                    ])
                ])
            )
        );
    }

    // TODO: Ensure no win if play air strike in enemy base
    public function testPlayCardOccupyEnemyBase()
    {
        $action = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->insert(
                    'playable_card',
                    ['type' => 'special-forces', 'player_id' => 66, '`order`' => 8]
                );
                $db->exec(SQLHelper::insertAll(
                    'battlefield_card',
                    [
                        ['player_id' => 66, 'type' => 'special-forces', 'x' => 0, 'y' => 1],
                        ['player_id' => 66, 'type' => 'special-forces', 'x' => 1, 'y' => 0]
                    ]
                ));
            })
            ->createActionInstanceForCurrentPlayer(66);

        $specialForcesId = $this->table->getDbConnection()
            ->fetchColumn('SELECT id FROM playable_card WHERE type = "special-forces" AND player_id = 66');
        $action->stubArgs([
            'id' => $specialForcesId,
            'x' => 0,
            'y' => -1
        ])->playCard();

        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            not(hasItem(hasEntry('id', $specialForcesId)))
        );
        assertThat(
            $this->table->fetchDbRows('battlefield_card', ['x' => 0, 'y' => -1]),
            contains(
                M\hasEntries([
                    'type' => 'special-forces',
                    'player_id' => 66,
                    'x' => 0,
                    'y' => -1
                ])
            )
        );
        assertThat(
            $action->getGame()->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'placedCard',
                    'log' => '${playerName} placed a ${typeName} card at ${x},${y}',
                    'args' => M\hasEntries([
                        'playerId' => 66,
                        'playerName' => nonEmptyString(),
                        'typeName' => nonEmptyString(),
                        'typeKey' => nonEmptyString(),
                        'handCount' => 5,
                        'x' => 0,
                        'y' => -1
                    ])
                ]),
                M\hasEntries([
                    'playerId' => 66,
                    'type' => 'iPlacedCard',
                    'log' => '',
                    'args' => M\hasEntries([
                        'cardId' => $specialForcesId,
                        'x' => 0,
                        'y' => -1
                    ])
                ]),
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'newScores',
                    'log' => '',
                    'args' => [
                        66 => ['score' => 1, 'scoreAux' => 0],
                        77 => ['score' => 0, 'scoreAux' => 0]
                    ]
                ]),
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'endOfGame',
                    'log' => ''
                ])
            )
        );
        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder(
                M\hasEntries([
                    'player_id' => 66,
                    'player_score' => 1
                ]),
                M\hasEntries([
                    'player_id' => 77,
                    'player_score' => 0
                ])
            )
        );
        // TODO: Assert gone to end game state
    }

    public function testPlayCardThatDoesntExist()
    {
        $this->expectException('BgaUserException');

        $action = $this->table
            ->setupNewGame()
            ->createActionInstanceForCurrentPlayer(66)
            ->stubArgs([
                'id' => -999999,
                'x' => 0,
                'y' => 1
            ]);

        $action->playCard();
    }

    public function testPlayCardInInvalidPosition()
    {
        $this->expectException('BgaUserException');

        $action = $this->table
            ->setupNewGame()
            ->createActionInstanceForCurrentPlayer(66);
        $card = $this->getNonAirStrikePlayableCardForPlayer(66);

        $action->stubArgs([
            'id' => $card['id'],
            'x' => 10,
            'y' => 10
        ])->playCard();
    }

    public function testPlayAirStrike()
    {
        $action = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->insert('battlefield_card', ['type' => 'infantry', 'player_id' => 77, 'x' => 0, 'y' => -1]);
                $db->update('player', ['player_score_aux' => 1], ['player_id' => 77]);
            })
            ->createActionInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');

        $action->stubArgs([
            'id' => $airStrikeId,
            'x' => 0,
            'y' => -1
        ])->playCard();

        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            not(hasItem(hasEntry('id', $airStrikeId)))
        );
        assertThat(
            $this->table->fetchDbRows('battlefield_card', ['x' => 0, 'y' => -1]),
            emptyArray()
        );
        assertThat(
            $this->table->fetchDbRows('player'),
            containsInAnyOrder(
                M\hasEntries(['player_id' => 66, 'player_score_aux' => 1]),
                M\hasEntries(['player_id' => 77, 'player_score_aux' => 1])
            )
        );
        $expectedLog = '${playerName} played an ${typeName} card destroying the ${destroyedType} card at ${x},${y}';
        assertThat(
            $action->getGame()->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'playedAirStrike',
                    'log' => $expectedLog,
                    'args' => M\hasEntries([
                        'playerId' => 66,
                        'destroyedType' => 'Infantry',
                        'typeName' => 'Air Strike',
                        'playerName' => nonEmptyString(),
                        'numAirStrikes' => 1,
                        'x' => 0,
                        'y' => -1
                    ])
                ]),
                M\hasEntries([
                    'playerId' => 66,
                    'type' => 'iPlayedAirStrike',
                    'log' => '',
                    'args' => M\hasEntries([
                        'cardId' => $airStrikeId,
                        'x' => 0,
                        'y' => -1
                    ])
                ]),
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'newScores',
                    'log' => '',
                    'args' => [
                        66 => ['score' => 0, 'scoreAux' => 1],
                        77 => ['score' => 0, 'scoreAux' => 1]
                    ]
                ])
            )
        );
    }

    public function testPlayAirStrikeNotOccupied()
    {
        $this->expectException('BgaUserException');

        $action = $this->table
            ->setupNewGame()
            ->createActionInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');

        $action->stubArgs([
            'id' => $airStrikeId,
            'x' => 6,
            'y' => 6
        ])->playCard();
    }

    public function testPlayAirStrikeOnMyOwnCard()
    {
        $this->expectException('BgaUserException');

        $action = $this->table
            ->setupNewGame()
            ->withDbConnection(function (Connection $db) {
                $db->insert('battlefield_card', ['type' => 'infantry', 'player_id' => 66, 'x' => 0, 'y' => -1]);
            })
            ->createActionInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');

        $action->stubArgs([
            'id' => $airStrikeId,
            'x' => 0,
            'y' => -1
        ])->playCard();
    }

    /**
     * @param int $playerId
     * @return array
     */
    private function getNonAirStrikePlayableCardForPlayer($playerId)
    {
        return $this->table
            ->createDbQueryBuilder()
            ->select('*')
            ->from('playable_card')
            ->where('player_id = :playerId')
            ->andWhere('type != :airStrikeType')
            ->setParameter(':playerId', $playerId)
            ->setParameter(':airStrikeType', 'air-strike')
            ->execute()
            ->fetch();
    }
}
