<?php


namespace CortexPE\std;

use pocketmine\color\Color as RGBAColor;

final class MCPEColor
{
    /** @var string */
    private $name;
    /** @var string */
    private $fmtString;
    /** @var RGBAColor */
    private $color;

    public function __construct(string $name, string $fmtString, RGBAColor $color)
    {
        $this->name = $name;
        $this->fmtString = $fmtString;
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFormatString(): string
    {
        return $this->fmtString;
    }

    /**
     * @return RGBAColor
     */
    public function getColor(): RGBAColor
    {
        return $this->color;
    }
}