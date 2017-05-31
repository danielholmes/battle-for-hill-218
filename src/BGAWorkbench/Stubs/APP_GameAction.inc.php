<?php

abstract class APP_GameAction extends APP_Action
{
    protected function ajaxResponse($dummy = '')
    {
        if ($dummy != '') {
            throw new InvalidArgumentException("Game action cannot return any data");
        }
    }
}
