<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;
use BGAWorkbench\WorkbenchProjectConfig;
use Symfony\Component\Config\Definition\Processor;

class TableInstanceBuilder
{
    /**
     * @var WorkbenchProjectConfig
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
     * @param WorkbenchProjectConfig $config
     */
    private function __construct(WorkbenchProjectConfig $config)
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
     * @return TableInstance
     */
    public function build()
    {
        return new TableInstance($this->config, $this->players, $this->options);
    }

    /**
     * @param WorkbenchProjectConfig $config
     * @return TableInstanceBuilder
     */
    public static function create(WorkbenchProjectConfig $config)
    {
        return new self($config);
    }
}