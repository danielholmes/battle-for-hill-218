<?php

namespace TheBattleForHill218\Battlefield;

class Position
{
    /**
     * @var int
     */
    private $x;

    /**
     * @var int
     */
    private $y;

    /**
     * @param int $x
     * @param int $y
     */
    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param Position $other
     * @return boolean
     */
    public function equals(Position $other)
    {
        return $this->x === $other->getX() && $this->y === $other->getY();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Position({$this->x}, {$this->y})";
    }
}