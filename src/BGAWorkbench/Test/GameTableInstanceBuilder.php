<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;
use BGAWorkbench\ProjectWorkbenchConfig;
use Symfony\Component\Config\Definition\Processor;

class GameTableInstanceBuilder
{
    /**
     * @var ProjectWorkbenchConfig
     */
    private $config;

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
     * @param ProjectWorkbenchConfig $config
     */
    private function __construct(ProjectWorkbenchConfig $config)
    {
        $this->config = $config;
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
     * @param int $amount
     * @return self
     */
    public function setRandomPlayers($amount)
    {
        return $this->setPlayers(array_fill(0, $amount, array()));
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
        return new GameTableInstance($this->config, $this->players, $this->options);
    }

    /**
     * @param ProjectWorkbenchConfig $config
     * @return GameTableInstanceBuilder
     */
    public static function create(ProjectWorkbenchConfig $config)
    {
        return new self($config);
    }
}