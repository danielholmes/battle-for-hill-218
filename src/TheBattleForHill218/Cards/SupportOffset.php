<?php

namespace TheBattleForHill218\Cards;

class SupportOffset
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
        $this->x = $this->validateOffset($xOffset);
        $this->y = $this->validateOffset($yOffset);
    }

    /**
     * @param int $offset
     * @return int
     */
    private function validateOffset($offset)
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
    public static function borderPattern()
    {
        return array(
            new self(0, 1),
            new self(1, 1),
            new self(1, 0),
            new self(1, -1),
            new self(0, -1),
            new self(-1, -1),
            new self(-1, 0),
            new self(-1, 1),
        );
    }
}
