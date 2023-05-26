<?php

namespace CortexPE\std\math;

class Grid2DLinearArray
{
    private $height;
    private $width;

    public function __construct(int $height, int $width)
    {
        $this->height = $height;
        $this->width = $width;
    }

    public function getLinearIndex(int $y, int $x): ?int
    {
        if ($x < 0 || $x >= $this->width || $y < 0 || $y >= $this->height) {
            return null;
        }
        return $this->width * $y + $x;
    }
}