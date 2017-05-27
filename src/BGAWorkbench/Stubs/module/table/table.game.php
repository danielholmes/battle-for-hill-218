<?php

class APP_Object
{

}

class APP_DbObject extends APP_Object
{
    /**
     * @param string $sql
     */
    public static function DbQuery($sql)
    {
        // TODO: Run sql
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

class Table extends APP_GameClass
{
    /**
     * @var Gamestate
     */
    public $gamestate;

    public function __construct()
    {
        $this->gamestate = new Gamestate();
    }

    protected function setupNewGame($players, $options = array()) { }

    public function initGameStateLabels($labels) { }

    public function reattributeColorsBasedOnPreferences($players, $colors) { }

    public function reloadPlayersBasicInfos() { }

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