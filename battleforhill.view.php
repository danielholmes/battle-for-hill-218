<?php

require_once(APP_BASE_PATH . "view/common/game.view.php");

/**
 * @property BattleForHill $game
 */
class view_battleforhill_battleforhill extends game_view
{
    public function getGameName()
    {
        return "battleforhill";
    }

    public function build_page($viewArgs)
    {
        global $g_user;
        $this->page->begin_block("battleforhill_battleforhill", "player_cards");

        $currentPlayerId = (int) $g_user->get_id();
        $players = $this->game->loadPlayersBasicInfos();

        $this->tpl['YOUR_HAND'] = self::_('Your hand');

        // Spectator
        if (!isset($players[$currentPlayerId])) {
            $this->tpl['GAME_CONTAINER_CLASS'] = '';
            return;
        }

        if ($players[$currentPlayerId]['player_color'] !== BattleForHill::DOWNWARD_PLAYER_COLOR) {
            $this->tpl['GAME_CONTAINER_CLASS'] = 'viewing-as-upwards-player';
        } else {
            $this->tpl['GAME_CONTAINER_CLASS'] = '';
        }

        $this->page->insert_block('player_cards');
    }
}
