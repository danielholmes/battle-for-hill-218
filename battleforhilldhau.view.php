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
            $this->tpl['GAME_CONTAINER_CLASS'] = '';
            return;
        }

        if ($players[$currentPlayerId]['player_color'] !== BattleForHillDhau::DOWNWARD_PLAYER_COLOR) {
            $this->tpl['GAME_CONTAINER_CLASS'] = 'viewing-as-upwards-player';
        } else {
            $this->tpl['GAME_CONTAINER_CLASS'] = '';
        }

        $this->page->insert_block('player_cards');
    }
}
