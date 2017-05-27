<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;
use BGAWorkbench\ProjectWorkbenchConfig;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class GameTableInstance
{
    /**
     * @var ProjectWorkbenchConfig
     */
    private $config;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @var Connection
     */
    private $dbConnection;

    /**
     * @var Connection
     */
    private $dbSchemaConnection;

    /**
     * @var array
     */
    private $players;

    /**
     * @var array
     */
    private $options;

    /**
     * @var boolean
     */
    private $databaseCreated;

    /**
     * @var Configuration
     */
    private $dbConfig;

    /**
     * @param ProjectWorkbenchConfig $config
     * @param array $players
     * @param array $options
     */
    public function __construct(ProjectWorkbenchConfig $config, array $players, array $options)
    {
        $this->config = $config;
        $this->project = $config->loadProject();
        $this->databaseName = $this->project->getName() . '_' . md5(time());
        $this->players = $players;
        $this->options = $options;
        $this->databaseCreated = false;
        $this->dbConfig = new Configuration();
    }

    /**
     * @return array
     */
    private function getDbServerConnectionParams()
    {
        return array(
            'user' => $this->config->getTestDbUsername(),
            'password' => $this->config->getTestDbPassword(),
            'host' => '127.0.0.1',
            'driver' => 'pdo_mysql'
        );
    }

    /**
     * @return Connection
     */
    private function getDbConnection()
    {
        if ($this->dbConnection === null) {
            $this->dbConnection = DriverManager::getConnection(
                array_merge($this->getDbServerConnectionParams(), array('dbname' => $this->databaseName)),
                $this->dbConfig
            );
        }

        return $this->dbConnection;
    }

    /**
     * @return Connection
     */
    private function getDbSchemaConnection()
    {
        if ($this->dbSchemaConnection === null) {
            $this->dbSchemaConnection = DriverManager::getConnection(
                $this->getDbServerConnectionParams(),
                $this->dbConfig
            );
        }

        return $this->dbSchemaConnection;
    }

    /**
     * @return self
     */
    public function createDatabase()
    {
        if ($this->databaseCreated) {
            throw new \LogicException('Database already created');
        }

        $this->getDbSchemaConnection()->getSchemaManager()->createDatabase($this->databaseName);
        $this->createDatabaseTables();

        $this->databaseCreated = true;
        return $this;
    }

    private function createDatabaseTables()
    {
        $contents = @file_get_contents($this->project->getDbModelSqlFile()->getPathname());
        if ($contents === false) {
            throw new \RuntimeException("Couldn't read db schema");
        }

        $this->getDbConnection()->executeUpdate($contents);
    }

    /**
     * @return self
     */
    public function dropDatabase()
    {
        if (!$this->databaseCreated) {
            throw new \LogicException('Database not created');
        }

        $this->getDbSchemaConnection()->getSchemaManager()->dropDatabase($this->databaseName);

        $this->databaseCreated = false;
        return $this;
    }

    /**
     * @return \Table
     */
    public function setupNewGame()
    {
        $table = $this->project->createGameInstance();

        // TODO: Find out how called in prod
        $gameClass = new \ReflectionClass($table);
        $setupNewGame = $gameClass->getMethod('setupNewGame');
        $setupNewGame->setAccessible(true);

        // TODO: Find if this could be done on instance - is it actually static?
        call_user_func(array($gameClass->getName(), 'stubGameInfos'), $this->project->getGameInfos());

        $setupNewGame->invoke($table, $this->players, $this->options);

        return $table;
    }
}