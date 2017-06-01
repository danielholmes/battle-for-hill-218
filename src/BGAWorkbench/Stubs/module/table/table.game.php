<?php

use BGAWorkbench\Test\Notification;
use Doctrine\DBAL\Connection;

class feException extends Exception
{

}

class BgaSystemException extends feException
{

}

class APP_Object
{

}

class APP_DbObject extends APP_Object
{
    ////////////////////////////////////////////////////////////////////////
    // Testing methods
    private static $affectedRows = 0;

    /**
     * @param string $sql
     */
    public static function DbQuery($sql)
    {
        self::$affectedRows = self::getDbConnection()->executeQuery($sql)->rowCount();
    }

    /**
     * @return int
     */
    public static function DbAffectedRow()
    {
        return self::$affectedRows;
    }

    /**
     * @param string $sql
     * @param boolean $bSingleValue
     * @return array
     */
    protected function getCollectionFromDB($sql, $bSingleValue = false)
    {
        $rows = self::getObjectListFromDB($sql);
        $result = array();
        foreach ($rows as $row) {
            if ($bSingleValue) {
                $key = reset($row);
                $result[$key] = next($row);
            } else {
                $result[reset($row)] = $row;
            }
        }

        return $result;
    }

    /**
     * @param string $sql
     * @param boolean $bUniqueValue
     * @return array
     */
    protected static function getObjectListFromDB($sql, $bUniqueValue = false)
    {
        return self::getDbConnection()->fetchAll($sql);
    }

    /**
     * @param string $sql
     * @return array
     * @throws BgaSystemException
     */
    protected function getNonEmptyObjectFromDB($sql)
    {
        $rows = $this->getObjectListFromDB($sql);
        if (count($rows) !== 1) {
            throw new BgaSystemException('Expected exactly one result');
        }

        return $rows[0];
    }

    /**
     * @param string $sql
     * @return mixed
     */
    protected static function getUniqueValueFromDB($sql)
    {
        return self::getDbConnection()->fetchColumn($sql);
    }

    /**
     * @var Connection
     */
    private static $connection;

    /**
     * @param Connection $connection
     */
    public static function setDbConnection(Connection $connection)
    {
        self::$connection = $connection;
    }

    /**
     * @return Connection
     */
    private static function getDbConnection()
    {
        if (self::$connection === null) {
            throw new \RuntimeException('No db connection set');
        }
        return self::$connection;
    }
}

class APP_GameClass extends APP_DbObject
{

}

class Gamestate
{
    public function setAllPlayersMultiactive()
    {
    }

    public function setPlayerNonMultiactive($player_id, $next_state)
    {
        return false;
    }

    public function nextState($action = '')
    {
    }

    public function changeActivePlayer($player_id)
    {
    }
}

abstract class Table extends APP_GameClass
{
    /**
     * @var Gamestate
     */
    public $gamestate;

    public function __construct()
    {
        $this->gamestate = new Gamestate();
    }

    abstract protected function setupNewGame($players, $options = array());

    public function initGameStateLabels($labels)
    {
    }

    public function reattributeColorsBasedOnPreferences($players, $colors)
    {
    }

    public function reloadPlayersBasicInfos()
    {
    }

    protected function activeNextPlayer()
    {
    }

    public function checkAction($actionName, $bThrowException = true)
    {
        return true;
    }

    /**
     * @return string
     */
    public function getActivePlayerName()
    {
    }

    ////////////////////////////////////////////////////////////////////////
    // Testing methods
    /**
     * @var array[]
     */
    private $notifications = [];

    /**
     * @return array[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    public function resetNotifications()
    {
        $this->notifications = [];
    }

    /**
     * @param string $notification_type
     * @param string $notification_log
     * @param array $notification_args
     */
    public function notifyAllPlayers($notification_type, $notification_log, $notification_args)
    {
        $this->notifyPlayer('all', $notification_type, $notification_log, $notification_args);
    }

    /**
     * @param int $player_id
     * @param string $notification_type
     * @param string $notification_log
     * @param array $notification_args
     */
    public function notifyPlayer($player_id, $notification_type, $notification_log, $notification_args)
    {
        if ($notification_log === null) {
            throw new \InvalidArgumentException('Use empty string for notification_log instead of null');
        }
        $this->notifications[] = [
            'playerId' => $player_id,
            'type' => $notification_type,
            'log' => $notification_log,
            'args' => $notification_args
        ];
    }

    /**
     * @var int
     */
    private $currentPlayerId;

    /**
     * @return int
     */
    protected function getCurrentPlayerId()
    {
        if ($this->currentPlayerId === null) {
            throw new \RuntimeException('Not a player bounded instance');
        }
        return $this->currentPlayerId;
    }

    /**
     * @todo get from getCurrentPlayerId table load
     * @return string
     */
    protected function getCurrentPlayerName()
    {
        return null;
    }

    /**
     * @todo get from getCurrentPlayerId table load
     * @return string
     */
    protected function getCurrentPlayerColor()
    {
        return null;
    }

    /**
     * @param int $currentPlayerId
     */
    public function stubCurrentPlayerId($currentPlayerId)
    {
        $this->currentPlayerId = $currentPlayerId;
    }

    /**
     * @var int
     */
    private $activePlayerId;

    /**
     * @return int
     */
    public function getActivePlayerId()
    {
        return $this->activePlayerId;
    }

    /**
     * @param int $activePlayerId
     * @return self
     */
    public function stubActivePlayerId($activePlayerId)
    {
        $this->activePlayerId = $activePlayerId;
        return $this;
    }

    /**
     * @var array|null
     */
    private static $stubbedGameInfos = null;

    /**
     * @param array $gameInfos
     */
    public static function stubGameInfos(array $gameInfos)
    {
        self::$stubbedGameInfos = $gameInfos;
    }

    /**
     * @param string $name
     * @return array
     */
    public static function getGameInfosForGame($name)
    {
        return self::$stubbedGameInfos;
    }

    /**
     * @var array|null
     */
    private $stubbedPlayersBasicInfos;

    /**
     * @param array $stubbedPlayersBasicInfos
     */
    public function stubPlayersBasicInfos(array $stubbedPlayersBasicInfos)
    {
        $this->stubbedPlayersBasicInfos = $stubbedPlayersBasicInfos;
    }

    /**
     * @return array
     */
    public function loadPlayersBasicInfos()
    {
        if ($this->stubbedPlayersBasicInfos === null) {
            throw new RuntimeException('PlayersBasicInfos not stubbed');
        }
        return $this->stubbedPlayersBasicInfos;
    }
}
