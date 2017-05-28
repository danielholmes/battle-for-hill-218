<?php

use Doctrine\DBAL\Connection;

class APP_Object
{

}

class APP_DbObject extends APP_Object
{
    ////////////////////////////////////////////////////////////////////////
    // Testing methods
    /**
     * @param string $sql
     */
    public static function DbQuery($sql)
    {
        self::getDbConnection()->executeQuery($sql);
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
    // TODO: Test
    public function setAllPlayersMultiactive() { }
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

    public function initGameStateLabels($labels) { }

    public function reattributeColorsBasedOnPreferences($players, $colors) { }

    public function reloadPlayersBasicInfos() { }

    ////////////////////////////////////////////////////////////////////////
    // Testing methods
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
}