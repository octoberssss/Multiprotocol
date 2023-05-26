<?php


namespace CortexPE\std\math;


class EloCalculator
{
    /** @var float */
    private $player1Percentage;
    /** @var float */
    private $player2Percentage;
    /** @var int */
    private $kFactor;

    public function __construct(int $player1Elo, int $player2Elo, int $kFactor)
    {
        $this->player1Percentage = $this->calculatePercentage($player2Elo - $player1Elo);
        $this->player2Percentage = $this->calculatePercentage($player1Elo - $player2Elo);
        $this->kFactor = $kFactor;
    }

    public function calculatePercentage(int $delta): float
    {
        return 1 / (1 + (10 ** ($delta / 400)));
    }

    /**
     * Calculates the given elo upon player 1 win
     *
     * @return int
     */
    public function onPlayer1Win(): int
    {
        return round($this->kFactor * (1 - $this->player1Percentage));
    }

    /**
     * Calculates the given elo upon player 2 win
     *
     * @return int
     */
    public function onPlayer2Win(): int
    {
        return round($this->kFactor * (1 - $this->player2Percentage));
    }

    /**
     * Calculates the given elo to player 1 upon draw
     *
     * @return int
     */
    public function onPlayer1Draw(): int
    {
        return round($this->kFactor * (0.5 - $this->player1Percentage));
    }

    /**
     * Calculates the given elo to player 2 upon draw
     *
     * @return int
     */
    public function onPlayer2Draw(): int
    {
        return round($this->kFactor * (0.5 - $this->player2Percentage));
    }

    /**
     * Calculates the given elo upon player 1 loss
     *
     * @return int
     */
    public function onPlayer1Loss(): int
    {
        return round($this->kFactor * -$this->player1Percentage);
    }

    /**
     * Calculates the given elo upon player 2 loss
     *
     * @return int
     */
    public function onPlayer2Loss(): int
    {
        return round($this->kFactor * -$this->player2Percentage);
    }
}