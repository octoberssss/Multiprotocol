<?php


namespace CortexPE\std\seq;


class SequentialDifference
{
    /** @var int */
    private $oldIndex;
    /** @var int */
    private $newIndex;
    /** @var mixed */
    private $item;

    public function __construct(int $oldIndex, int $newIndex, $item)
    {
        $this->oldIndex = $oldIndex;
        $this->newIndex = $newIndex;
        $this->item = $item;
    }

    /**
     * @return int
     */
    public function getOldIndex(): int
    {
        return $this->oldIndex;
    }

    /**
     * @return int
     */
    public function getNewIndex(): int
    {
        return $this->newIndex;
    }

    /**
     * @return int
     */
    public function getDiff(): int
    {
        return abs($this->oldIndex - $this->newIndex);
    }

    /**
     * @return mixed
     */
    public function getItem(): mixed
    {
        return $this->item;
    }
}