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
    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return int
     */
    public function getX() : int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY() : int
    {
        return $this->y;
    }

    /**
     * @param int $xOffset
     * @param int $yOffset
     * @return Position
     */
    public function offset(int $xOffset, int $yOffset)
    {
        return new Position($this->x + $xOffset, $this->y + $yOffset);
    }

    /**
     * @param Position $other
     * @return Position[]
     */
    public function gridTo(Position $other) : array
    {
        if ($this == $other) {
            return [$this];
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
     * @return Position
     */
    public function flipY() : Position
    {
        return new Position($this->x, -$this->y);
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return "Position({$this->x}, {$this->y})";
    }
}
