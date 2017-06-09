<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use Functional as F;
use TheBattleForHill218\SQLHelper;

class PlayCardTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayers([
                ['player_id' => 66, 'base_side' => '1'],
                ['player_id' => 77, 'base_side' => '-1']
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
            ->createActionInstanceForCurrentPlayer(66);
        // TODO: Insert special forces card into playable
        $card = $this->getSpecialForcesCardForPlayer(66);
        $this->table->getDbConnection()->exec(SQLHelper::insertAll(
            'battlefield_card',
            [
                ['player_id' => 66, 'type' => 'special-forces', 'x' => 0, 'y' => 1],
                ['player_id' => 66, 'type' => 'special-forces', 'x' => 1, 'y' => 0]
            ]
        ));

        $action->stubArgs([
            'id' => $card['id'],
            'x' => 0,
            'y' => -1
        ])->playCard();

        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            not(hasItem(hasEntry('id', $card['id'])))
        );
        assertThat(
            $this->table->fetchDbRows('battlefield_card', ['x' => 0, 'y' => -1]),
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
            ->createActionInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');
        $this->table
            ->getDbConnection()
            ->insert('battlefield_card', ['type' => 'infantry', 'player_id' => 77, 'x' => 0, 'y' => -1]);

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
            $action->getGame()->getNotifications(),
            containsInAnyOrder(
                M\hasEntries([
                    'playerId' => 'all',
                    'type' => 'playedAirStrike',
                    'log' => '${playerName} played an air strike card at ${x},${y}',
                    'args' => M\hasEntries([
                        'playerId' => 66,
                        'playerName' => nonEmptyString(),
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
            ->createActionInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');
        $this->table
            ->getDbConnection()
            ->insert('battlefield_card', ['type' => 'infantry', 'player_id' => 66, 'x' => 0, 'y' => -1]);

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

    /**
     * @param int $playerId
     * @return array[]
     */
    private function getSpecialForcesCardForPlayer($playerId)
    {
        return $this->table
            ->createDbQueryBuilder()
            ->select('*')
            ->from('deck_card')
            ->where('player_id = :playerId')
            ->andWhere('type = :specialForcesType')
            ->setParameter(':playerId', $playerId)
            ->setParameter(':specialForcesType', 'special-forces')
            ->execute()
            ->fetch();
    }
}
