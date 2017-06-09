<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project\WorkbenchProjectConfig;
use Faker\Factory;
use Faker\Generator;
use Functional as F;

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
     * @var Generator
     */
    private $faker;

    /**
     * @param WorkbenchProjectConfig $config
     */
    private function __construct(WorkbenchProjectConfig $config)
    {
        $this->config = $config;
        $this->options = [];
        $this->faker = Factory::create();
    }

    /**
     * @param array $players
     * @return self
     */
    public function setPlayers(array $players)
    {
        $this->players = F\map(
            $players,
            function (array $player, $i) {
                return array_merge(
                    array(
                        'player_no' => $i + 1,
                        'player_canal' => md5($i + time()),
                        'player_color' => substr($this->faker->hexColor, 1),
                        'player_name' => $this->faker->firstName,
                        'player_avatar' => '000000',
                        'player_is_admin' => 0,
                        'player_beginner' => 0,
                        'player_is_ai' => 0,
                        'player_table_order' => $i
                    ),
                    $player
                );
            }
        );
        return $this;
    }

    /**
     * @param array $ids
     * @return self
     */
    public function setPlayersWithIds(array $ids)
    {
        return $this->setPlayers(array_map(function ($id) {
            return ['player_id' => $id];
        }, $ids));
    }

    /**
     * @param int $amount
     * @return self
     */
    public function setRandomPlayers($amount)
    {
        return $this->setPlayers(array_fill(0, $amount, []));
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
