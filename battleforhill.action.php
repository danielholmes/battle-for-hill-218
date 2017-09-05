<?php

use Functional as F;

/**
 * @property BattleForHill game
 */
class action_battleforhill extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = $this->getArg("table", AT_posint, true);
        } else {
            $this->view = "battleforhill_battleforhill";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function returnToDeck()
    {
        $this->setAjaxMode();

        $joinedIds = $this->getArg('ids', AT_numberlist, true);
        $ids = F\map(explode(',', $joinedIds), function ($id) {
            return intval($id);
        });
        $this->game->returnToDeck($ids);

        $this->ajaxResponse();
    }

    public function playCard()
    {
        $this->setAjaxMode();

        $cardId = $this->getArg('id', AT_int, true);
        $x = (int) $this->getArg('x', AT_int, true);
        $y = (int) $this->getArg('y', AT_int, true);
        $this->game->playCard($cardId, $x, $y);

        $this->ajaxResponse();
    }

    public function chooseAttack()
    {
        $this->setAjaxMode();

        $x = (int) $this->getArg('x', AT_int, true);
        $y = (int) $this->getArg('y', AT_int, true);
        $this->game->chooseAttack($x, $y);

        $this->ajaxResponse();
    }
}
