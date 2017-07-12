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
    public function __construct(int $xOffset, int $yOffset)
    {
        $this->x = $this->validateOffset($xOffset);
        $this->y = $this->validateOffset($yOffset);
    }

    /**
     * @param int $offset
     * @return int
     */
    private function validateOffset(int $offset) : int
    {
        if (!in_array($offset, [1, 0, -1], true)) {
            throw new \InvalidArgumentException('Offsets must be 1, 0, -1');
        }
        return $offset;
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
     * @return self[]
     */
    public static function plusPattern() : array
    {
        return [
            new self(0, 1),
            new self(1, 0),
            new self(0, -1),
            new self(-1, 0)
        ];
    }

    /**
     * @return self[]
     */
    public static function borderPattern() : array
    {
        return [
            new self(0, 1),
            new self(1, 1),
            new self(1, 0),
            new self(1, -1),
            new self(0, -1),
            new self(-1, -1),
            new self(-1, 0),
            new self(-1, 1),
        ];
    }
}
