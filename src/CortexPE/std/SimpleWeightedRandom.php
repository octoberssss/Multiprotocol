<?php


namespace CortexPE\std;


class SimpleWeightedRandom
{
    private $entries = [];
    private $map = [];
    private $weights = [];
    private $weightSum = 0;

    public function addEntry($entry, int $weight): int
    {
        $this->entries[$k = count($this->entries)] = $entry;
        for ($i = 0; $i < $weight; $i++) {
            $this->map[] = $k;
        }
        $this->weights[$k] = $weight;
        $this->weightSum += $weight;
        return $k;
    }

    public function getChance(int $k): float
    {
        return $this->weights[$k] / $this->weightSum;
    }

    public function getWeightedRandom()
    {
        return $this->entries[$this->map[array_rand($this->map)]];
    }

    public function getNonWeightedRandom()
    {
        return $this->entries[array_rand($this->entries)];
    }
}