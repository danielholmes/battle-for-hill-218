<?php

namespace TheBattleForHill218\Commands;

class TileSpec
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $cssName;

    /**
     * @param string $fileName
     * @param string $cssName
     */
    public function __construct($fileName, $cssName = null)
    {
        $this->fileName = $fileName;
        $this->cssName = $cssName;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return bool
     */
    public function includeInCss()
    {
        return $this->cssName !== null;
    }

    /**
     * @return string
     */
    public function getCssName()
    {
        return $this->cssName;
    }
}
