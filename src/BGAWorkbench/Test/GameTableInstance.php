<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;

class GameTableInstance
{
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
     * @param Project $project
     * @param array $players
     * @param array $options
     */
    public function __construct(Project $project, array $players, array $options)
    {
        $this->project = $project;
        $this->players = $players;
        $this->options = $options;
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