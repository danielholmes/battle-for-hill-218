<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;
use Symfony\Component\Config\Definition\Processor;

class GameTableInstanceBuilder
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
     * @var Processor
     */
    private $configProcessor;

    /**
     * @param Project $project
     */
    private function __construct(Project $project)
    {
        $this->project = $project;
        $this->options = array();
        $this->configProcessor = new Processor();
    }

    /**
     * @param array $players
     * @return self
     */
    public function setPlayers(array $players)
    {
        $this->players = $this->configProcessor->processConfiguration(new PlayersConfiguration(), array($players));
        return $this;
    }

    /**
     * @param array $options
     * @return self
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return GameTableInstance
     */
    public function build()
    {
        return new GameTableInstance($this->project, $this->players, $this->options);
    }

    /**
     * @param Project $project
     * @return GameTableInstanceBuilder
     */
    public static function create(Project $project)
    {
        return new self($project);
    }
}