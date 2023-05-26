<?php


namespace CortexPE\std\math;


class RollingAverage
{
    /** @var float */
    private $avg = 0;
    /** @var int */
    private $count = 0;

    public function process(float $data): void
    {
        $this->avg = ($this->avg * $this->count + $data) / ($this->count + 1);
        $this->count++;
    }

    /**
     * @return float
     */
    public function getAvg(): float
    {
        return $this->avg;
    }
}