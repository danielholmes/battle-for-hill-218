<?php

namespace TheBattleForHill218\Cards;

class AttackOffset
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
     * @param int $xOffset
     * @param int $yOffset
     */
    public function __construct($xOffset, $yOffset)
    {
        $this->x = $this->validateXOffset($xOffset);
        $this->y = $this->validateYOffset($yOffset);
    }

    /**
     * @param int $offset
     * @return int
     */
    private function validateXOffset($offset)
    {
        if (!in_array($offset, array(3, 2, 1, 0, -1), true)) {
            throw new \InvalidArgumentException('Offsets must be 3, 2, 1, 0, -1');
        }
        return $offset;
    }

    /**
     * @param int $offset
     * @return int
     */
    private function validateYOffset($offset)
    {
        if (!in_array($offset, array(1, 0, -1), true)) {
            throw new \InvalidArgumentException('Offsets must be 1, 0, -1');
        }
        return $offset;
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
     * @return self
     */
    public function flipY()
    {
        return new self($this->getX(), -$this->getY());
    }

    /**
     * @return self[]
     */
    public static function plusPattern()
    {
        return array(
            new self(0, 1),
            new self(1, 0),
            new self(0, -1),
            new self(-1, 0)
        );
    }

    /**
     * @return self[]
     */
    public static function crossPattern()
    {
        return array(
            new self(1, 1),
            new self(1, -1),
            new self(-1, -1),
            new self(-1, 1)
        );
    }

    /**
     * @return self[]
     */
    public static function arrowPattern()
    {
        return array(
            new self(-1, 2),
            new self(0, 2),
            new self(1, 2),
            new self(0, 3)
        );
    }
}