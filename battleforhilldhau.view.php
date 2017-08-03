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
        $this->page->begin_block("battleforhilldhau_battleforhilldhau", "player_cards");

        $currentPlayerId = (int) $g_user->get_id();
        $players = $this->game->loadPlayersBasicInfos();

        // Spectator
        if (!isset($players[$currentPlayerId])) {
            $this->tpl['GAME_CONTAINER_CLASS'] = 'spectator-view';
            return;
        }

        $this->page->insert_block("player_cards");
    }
}
