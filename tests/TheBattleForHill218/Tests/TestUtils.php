<?php

namespace TheBattleForHill218\Tests;

use Doctrine\DBAL\Connection;
use Functional as F;
use TheBattleForHill218\Cards\AirStrikeCard;

class TestUtils
{
    /**
     * @param Connection $db
     * @param int $playerId
     * @return int[]
     */
    public static function get2RandomReturnableIds(Connection $db, $playerId)
    {
        return F\pluck(
            $db->fetchAll(
                'SELECT id FROM playable_card WHERE player_id = :playerId AND type != :type LIMIT 2',
                ['playerId' => $playerId, 'type' => AirStrikeCard::typeKey()]
            ),
            'id'
        );
    }

    /**
     * @param \BattleForHill $game
     * @param Connection $db
     * @param int $playerId
     */
    public static function return2RandomCards(\BattleForHill $game, Connection $db, $playerId)
    {
        $game->stubCurrentPlayerId($playerId)->returnToDeck(self::get2RandomReturnableIds($db, $playerId));
    }
}
