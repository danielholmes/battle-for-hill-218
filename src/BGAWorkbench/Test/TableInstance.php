<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;
use BGAWorkbench\WorkbenchProjectConfig;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;

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
     * @param WorkbenchProjectConfig $config
     * @param array $players
     * @param array $options
     */
    public function __construct(WorkbenchProjectConfig $config, array $players, array $options)
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
        return [
            'user' => $this->config->getTestDbUsername(),
            'password' => $this->config->getTestDbPassword(),
            'host' => '127.0.0.1',
            'driver' => 'pdo_mysql'
        ];
    }

    /**
     * @param string $tableName
     * @param array $conditions
     * @return array
     */
    public function fetchDbRows($tableName, array $conditions = array())
    {
        if (!$this->databaseCreated) {
            throw new \RuntimeException('Database not created');
        }

        $qb = $this->getOrCreateDbConnection()
            ->createQueryBuilder()
            ->select('*')
            ->from($tableName);
        foreach ($conditions as $name => $value) {
            $qb = $qb->andWhere("{$name} = :{$name}")
                    ->setParameter(":{$name}", $value);
        }
        return $qb->execute()->fetchAll();
    }

    /**
     * @return Connection
     */
    private function getOrCreateDbConnection()
    {
        if ($this->dbConnection === null) {
            $this->dbConnection = DriverManager::getConnection(
                array_merge($this->getDbServerConnectionParams(), ['dbname' => $this->databaseName]),
                $this->dbConfig
            );
        }

        return $this->dbConnection;
    }

    /**
     * @return Connection
     */
    private function getOrCreateDbSchemaConnection()
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

        $this->getOrCreateDbSchemaConnection()->getSchemaManager()->createDatabase($this->databaseName);
        $this->createDatabaseTables();

        $this->databaseCreated = true;
        return $this;
    }

    private function createDatabaseTables()
    {
        $frameworkSql = @file_get_contents(join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Stubs', 'dbmodel.sql']));
        if ($frameworkSql === false) {
            throw new \RuntimeException("Couldn't read framework db schema");
        }

        $projectSql = @file_get_contents($this->project->getDbModelSqlFile()->getPathname());
        if ($projectSql === false) {
            throw new \RuntimeException("Couldn't read db schema");
        }

        $this->getOrCreateDbConnection()->executeUpdate($frameworkSql);
        $this->getOrCreateDbConnection()->executeUpdate($projectSql);
    }

    /**
     * @return self
     */
    public function dropDatabase()
    {
        if (!$this->databaseCreated) {
            throw new \LogicException('Database not created');
        }

        $this->getOrCreateDbSchemaConnection()->getSchemaManager()->dropDatabase($this->databaseName);

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

        call_user_func([$gameClass->getName(), 'stubGameInfos'], $this->project->getGameInfos());
        call_user_func([$gameClass->getName(), 'setDbConnection'], $this->getOrCreateDbConnection());

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