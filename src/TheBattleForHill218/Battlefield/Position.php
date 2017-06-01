<?php

namespace TheBattleForHill218\Battlefield;

use Functional as F;

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
     * @param int $xOffset
     * @param int $yOffset
     * @return Position
     */
    public function offset($xOffset, $yOffset)
    {
        return new Position($this->x + $xOffset, $this->y + $yOffset);
    }

    /**
     * @param Position $other
     * @return Position[]
     */
    public function gridTo(Position $other)
    {
        if ($this->equals($other)) {
            return array($this);
        }

        $minX = min($this->getX(), $other->getX());
        $maxX = max($this->getX(), $other->getX());
        $minY = min($this->getY(), $other->getY());
        $maxY = max($this->getY(), $other->getY());
        return F\flat_map(
            range($minX, $maxX),
            function ($x) use ($minY, $maxY) {
                return F\flat_map(
                    range($minY, $maxY),
                    function ($y) use ($x) {
                        return new Position($x, $y);
                    }
                );
            }
        );
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
