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
     * @var int
     */
    private $currentPlayerId;

    /**
     * @var \Table
     */
    private $table;

    /**
     * @param WorkbenchProjectConfig $config
     * @param array $players
     * @param array $options
     * @param int $currentPlayerId
     */
    public function __construct(WorkbenchProjectConfig $config, array $players, array $options, $currentPlayerId)
    {
        $this->config = $config;
        $this->project = $config->loadProject();
        $this->players = $players;
        $this->options = $options;
        $this->currentPlayerId = $currentPlayerId;
        $this->database = new DatabaseInstance(
            $this->project->getName() . '_' . md5(time()),
            $config->getTestDbUsername(),
            $config->getTestDbPassword(),
            [
                join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Stubs', 'dbmodel.sql']),
                $this->project->getDbModelSqlFile()->getPathname()
            ]
        );
        $this->table = $this->project->createTableInstance();
        $this->table->stubCurrentPlayerId($this->currentPlayerId);
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
     * @return self
     */
    public function setupNewGame()
    {
        $gameClass = new \ReflectionClass($this->table);
        call_user_func([$gameClass->getName(), 'stubGameInfos'], $this->project->getGameInfos());
        call_user_func([$gameClass->getName(), 'setDbConnection'], $this->database->getOrCreateConnection());
        $this->callProtectedAndReturn('setupNewGame', [$this->createPlayersById(), $this->options]);
        return $this;
    }

    /**
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function callProtectedAndReturn($methodName, array $args = array())
    {
        $gameClass = new \ReflectionClass($this->table);
        $method = $gameClass->getMethod($methodName);
        if (!$method->isProtected()) {
            throw new \RuntimeException("Method {$methodName} isn't protected");
        }

        $method->setAccessible(true);
        return $method->invokeArgs($this->table, $args);
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