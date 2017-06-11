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
        $this->x = $this->validateCoordinate($x);
        $this->y = $this->validateCoordinate($y);
    }

    /**
     * @param mixed $coord
     * @return int
     */
    private function validateCoordinate($coord)
    {
        if (!is_int($coord)) {
            $coordExport = var_export($coord, true);
            throw new \InvalidArgumentException("Coord should be an int {$coordExport}");
        }
        return $coord;
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
        if ($this == $other) {
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
     * @return Position
     */
    public function flipY()
    {
        return new Position($this->x, -$this->y);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Position({$this->x}, {$this->y})";
    }
}
