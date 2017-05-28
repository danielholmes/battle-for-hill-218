<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;
use BGAWorkbench\WorkbenchProjectConfig;

class TableInstance
{
    /**
     * @var WorkbenchProjectConfig
     */
    private $config;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var array
     */
    private $players;

    /**
     * @var array
     */
    private $options;

    /**
     * @param WorkbenchProjectConfig $config
     * @param array $players
     * @param array $options
     */
    public function __construct(WorkbenchProjectConfig $config, array $players, array $options)
    {
        $this->config = $config;
        $this->project = $config->loadProject();
        $this->players = $players;
        $this->options = $options;
        $this->database = new DatabaseInstance(
            $this->project->getName() . '_' . md5(time()),
            $config->getTestDbUsername(),
            $config->getTestDbPassword(),
            [
                join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Stubs', 'dbmodel.sql']),
                $this->project->getDbModelSqlFile()->getPathname()
            ]
        );
    }

    /**
     * @return self
     */
    public function createDatabase()
    {
        $this->database->create();
        return $this;
    }

    /**
     * @return self
     */
    public function dropDatabase()
    {
        $this->database->drop();
        return $this;
    }

    /**
     * @param string $tableName
     * @param array $conditions
     * @return array
     */
    public function fetchDbRows($tableName, array $conditions = array())
    {
        return $this->database->fetchRows($tableName, $conditions);
    }

    /**
     * @return \Table
     */
    public function setupNewGame()
    {
        $table = $this->project->createTableInstance();

        $gameClass = new \ReflectionClass($table);
        call_user_func([$gameClass->getName(), 'stubGameInfos'], $this->project->getGameInfos());
        call_user_func([$gameClass->getName(), 'setDbConnection'], $this->database->getOrCreateConnection());

        $setupNewGame = $gameClass->getMethod('setupNewGame');
        $setupNewGame->setAccessible(true);
        $setupNewGame->invoke($table, $this->createPlayersById(), $this->options);

        return $table;
    }

    /**
     * @return array
     */
    private function createPlayersById()
    {
        $ids = array_map(
            function($i, array $player) {
                if (isset($player['player_id'])) {
                    return $player['player_id'];
                }
                return $i;
            },
            range(1, count($this->players)),
            $this->players
        );
        return array_combine($ids, $this->players);
    }
}