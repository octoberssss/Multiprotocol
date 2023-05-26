<?php


namespace CortexPE\std\math\geometry;


use pocketmine\math\Vector2;

class ArchimedeanSpiral
{
    /** @var float */
    protected $currentAngle = 0;
    /** @var float */
    protected $anglePerTurn = 0.1; // angle in radians (0.1 = 5.72958)

    /** @var int */
    protected $centerRadius = 0; // center radius

    /** @var int */
    protected $spaceBetweenTurns = 5; // the space between turns

    public function getNextPoint(): Vector2
    {
        $this->currentAngle += $this->anglePerTurn;

        return new Vector2(
            ($this->centerRadius + $this->spaceBetweenTurns * $this->currentAngle) * cos($this->currentAngle),
            ($this->centerRadius + $this->spaceBetweenTurns * $this->currentAngle) * sin($this->currentAngle)
        );
    }

    public function reset(): void
    {
        $this->currentAngle = 0;
    }

    /**
     * Sets angle per turn in degrees
     *
     * @param float $anglePerIteration
     */
    public function setAnglePerIteration(float $anglePerIteration): void
    {
        $this->anglePerTurn = deg2rad($anglePerIteration);
    }

    /**
     * Gets angle per turn in degrees
     * @return float
     */
    public function getAnglePerTurn(): float
    {
        return rad2deg($this->anglePerTurn);
    }

    /**
     * @return int
     */
    public function getCenterRadius(): int
    {
        return $this->centerRadius;
    }

    /**
     * @param int $centerRadius
     */
    public function setCenterRadius(int $centerRadius): void
    {
        $this->centerRadius = $centerRadius;
    }

    /**
     * @return float
     */
    public function getCurrentAngle(): float
    {
        return $this->currentAngle;
    }

    /**
     * @return int
     */
    public function getSpaceBetweenTurns(): int
    {
        return $this->spaceBetweenTurns;
    }

    /**
     * @param int $spaceBetweenTurns
     */
    public function setSpaceBetweenTurns(int $spaceBetweenTurns): void
    {
        $this->spaceBetweenTurns = $spaceBetweenTurns;
    }
}