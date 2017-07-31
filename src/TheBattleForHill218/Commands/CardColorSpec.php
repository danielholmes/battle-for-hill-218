<?php

namespace TheBattleForHill218\Commands;

class CardColorSpec
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $color;

    /**
     * @param $fileName
     * @param $color
     */
    public function __construct($fileName, $color)
    {
        $this->fileName = $fileName;
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }
}
