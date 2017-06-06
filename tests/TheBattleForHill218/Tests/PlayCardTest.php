<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Test\HamcrestMatchers as M;
use PHPUnit\Framework\TestCase;
use BGAWorkbench\Test\TestHelp;
use Functional as F;

class PlayCardTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder()
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77]);
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
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);
        $card = $this->getNonAirStrikePlayableCardForPlayer(66);

        $game->playCard($card['id'], 0, 1);

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
            $game->getNotifications(),
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

    public function testPlayCardThatDoesntExist()
    {
        $this->expectException('BgaUserException');

        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);

        $game->playCard(-99999, 0, 1);
    }

    public function testPlayCardInInvalidPosition()
    {
        $this->expectException('BgaUserException');

        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);
        $card = $this->getNonAirStrikePlayableCardForPlayer(66);

        $game->playCard($card['id'], 10, 10);
    }

    public function testPlayAirStrike()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');
        $this->table
            ->getDbConnection()
            ->insert('battlefield_card', ['type' => 'infantry', 'player_id' => 77, 'x' => 0, 'y' => -1]);

        $game->playCard($airStrikeId, 0, -1);

        assertThat(
            $this->table->fetchDbRows('playable_card', ['player_id' => 66]),
            not(hasItem(hasEntry('id', $airStrikeId)))
        );
        assertThat(
            $this->table->fetchDbRows('battlefield_card', ['x' => 0, 'y' => -1]),
            emptyArray()
        );
        assertThat(
            $game->getNotifications(),
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

        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');

        $game->playCard($airStrikeId, 6, 6);
    }

    public function testPlayAirStrikeOnMyOwnCard()
    {
        $this->expectException('BgaUserException');

        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceForCurrentPlayer(66);
        $airStrikeId = $this->table
            ->fetchValue('SELECT id FROM playable_card WHERE type = "air-strike" AND player_id = 66');
        $this->table
            ->getDbConnection()
            ->insert('battlefield_card', ['type' => 'infantry', 'player_id' => 66, 'x' => 0, 'y' => -1]);

        $game->playCard($airStrikeId, 0, -1);
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
            ->setParameter(':playerId', $playerId)
            ->andWhere('type != :airStrikeType')
            ->setParameter(':airStrikeType', 'air-strike')
            ->execute()
            ->fetch();
    }
}
