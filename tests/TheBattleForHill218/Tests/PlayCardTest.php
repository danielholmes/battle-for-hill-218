<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\TableInstance;
use BGAWorkbench\Test\ProjectIntegrationTestCase;
use BGAWorkbench\Test\HamcrestMatchers as M;

class PlayCardTest extends ProjectIntegrationTestCase
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

    public function testPlayCardValid()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);

        $card = $this->table
            ->createDbQueryBuilder()
            ->select('*')
            ->from('playable_card')
            ->where('player_id = :playerId')
            ->setParameter(':playerId', 66)
            ->andWhere('type != :airStrikeType')
            ->setParameter(':airStrikeType', 'air-strike')
            ->execute()
            ->fetch();

        $game->playCard($card['id'], 0, 1);

        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            not(hasItem(hasEntry('id', $card['id'])))
        );
        assertThat(
            $this->table->fetchDbRows('battlefield_card', ['x' => 0, 'y' => 1]),
            contains(
                M::hasEntries([
                    'type' => $card['type'],
                    'player_id' => 66,
                    'x' => 0,
                    'y' => 1
                ])
            )
        );
        assertThat(
            $game->getNotifications(),
            containsInAnyOrder(
                M::hasEntries([
                    'playerId' => 66,
                    'type' => 'placedCard',
                    'log' => '${playerName} placed a ${typeName} card at ${x},${y}',
                    'args' => M::hasEntries([
                        'playerName' => nonEmptyString(),
                        'typeName' => nonEmptyString(),
                        'x' => 0,
                        'y' => 1
                    ])
                ]),
                M::hasEntries([
                    'playerId' => 77,
                    'type' => 'placedCard',
                    'log' => '${playerName} placed a ${typeName} card at ${x},${y}',
                    'args' => M::hasEntries([
                        'playerName' => nonEmptyString(),
                        'playerColor' => nonEmptyString(),
                        'typeName' => nonEmptyString(),
                        'typeKey' => nonEmptyString(),
                        'x' => 0,
                        'y' => 1
                    ])
                ]),
                M::hasEntries([
                    'playerId' => 66,
                    'type' => 'iPlacedCard',
                    'log' => '',
                    'args' => M::hasEntries([
                        'cardId' => $card['id'],
                        'playerColor' => nonEmptyString(),
                        'typeKey' => nonEmptyString(),
                        'x' => 0,
                        'y' => 1
                    ])
                ])
            )
        );
    }

    public function testPlayCardThatDoesntExist()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);

        // TODO:
        //$game->playCard(-99999, 0, 1);
    }

    // TODO: Airstrike card
    // TODO: Invalid placement position
}