<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project\Project;
use BGAWorkbench\Project\WorkbenchProjectConfig;

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
     * @var boolean
     */
    private $isSetup;

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
            $this->project->getName() . '_test',
            $config->getTestDbUsername(),
            $config->getTestDbPassword(),
            [
                join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Stubs', 'dbmodel.sql']),
                $this->project->getDbModelSqlFile()->getPathname()
            ]
        );
        $this->table = $this->project->createTableInstance();
        $this->table->stubCurrentPlayerId($this->currentPlayerId);
        $this->isSetup = false;
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
        if ($this->isSetup) {
            throw new \RuntimeException('Already setup');
        }

        $this->isSetup = true;

        $gameClass = new \ReflectionClass($this->table);
        call_user_func([$gameClass->getName(), 'stubGameInfos'], $this->project->getGameInfos());
        call_user_func([$gameClass->getName(), 'setDbConnection'], $this->database->getOrCreateConnection());
        $this->callProtectedAndReturn('setupNewGame', $this->createPlayersById(), $this->options);
        return $this;
    }

    /**
     * @return \Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $methodName
     * @param mixed $args,...
     * @return mixed
     */
    public function callProtectedAndReturn($methodName, $args = null)
    {
        $gameClass = new \ReflectionClass($this->table);
        $method = $gameClass->getMethod($methodName);
        if (!$method->isProtected()) {
            throw new \RuntimeException("Method {$methodName} isn't protected");
        }

        $method->setAccessible(true);
        $methodArgs = func_get_args();
        array_shift($methodArgs);
        return $method->invokeArgs($this->table, $methodArgs);
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

    /**
     * @param int $activePlayerId
     * @return self
     */
    public function setActivePlayer($activePlayerId)
    {
        $this->table->stubActivePlayerId($activePlayerId);
        return $this;
    }

    /**
     * @return self
     */
    public function resetNotificationTracking()
    {
        $this->table->resetNotifications();
        return $this;
    }

    /**
     * @return array[]
     */
    public function getTrackedNotifications()
    {
        return $this->table->getNotifications();
    }
}