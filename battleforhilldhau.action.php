<?php

use Functional as F;

/**
 * @property BattleForHillDhau game
 */
class action_battleforhilldhau extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "battleforhilldhau_battleforhilldhau";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function returnToDeck()
    {
        self::setAjaxMode();

        $joinedIds = self::getArg('ids', AT_numberlist, true);
        $ids = F\map(explode(',', $joinedIds), function($id) { return intval($id); });
        $this->game->returnToDeck($ids);

        self::ajaxResponse();
    }

    public function playCard()
    {
        self::setAjaxMode();

        $cardId = self::getArg('id', AT_int, true);
        $x = self::getArg('x', AT_int, true);
        $y = self::getArg('y', AT_int, true);
        $this->game->playCard($cardId, $x, $y);

        self::ajaxResponse();
    }
}
  

