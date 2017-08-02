<?php

require_once(APP_BASE_PATH . "view/common/game.view.php");

use Functional as F;

/**
 * @property BattleForHillDhau $game
 */
class view_battleforhilldhau_battleforhilldhau extends game_view
{
    public function getGameName()
    {
        return "battleforhilldhau";
    }

    public function build_page($viewArgs)
    {
        global $g_user;
        $currentPlayerId = (int) $g_user->get_id();

        $players = F\sort(
            F\map(
                $this->game->loadPlayersBasicInfos(),
                function (array $player) {
                    return array(
                        'id' => (int) $player['player_id'],
                        'name' => $player['player_name']
                    );
                }
            ),
            function (array $player1, array $player2) use ($currentPlayerId) {
                if ($player1['id'] === $currentPlayerId) {
                    return 1;
                }
                if ($player2['id'] === $currentPlayerId) {
                    return -1;
                }
                return strcmp($player1['name'], $player2['name']);
            }
        );

        $this->page->begin_block("battleforhilldhau_battleforhilldhau", "player_cards");
        foreach ($players as $player) {
            if ($player['id'] === $currentPlayerId) {
                $this->page->insert_block("player_cards");
            }
        }
    }
}
