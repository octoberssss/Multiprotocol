<?php


namespace CortexPE\std;


use JetBrains\PhpStorm\Pure;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

final class Vector3Utils
{

    private function __construct()
    {
    }

    public static function midpoint(Vector3 ...$points): Vector3
    {
        $avg = new Vector3(0, 0, 0);
        foreach ($points as $point) {
            $avg = $avg->addVector($point);
        }
        return $avg->divide(count($points));
    }

    public static function fromString(string $data): Vector3
    {
        $dat = explode(":", $data);
        if (count($dat) !== 3) {
            throw new \UnexpectedValueException("Expected string with format x:y:z. $data given");
        }
        /*foreach($dat as $k => $v){
            if($v === "-INF")$dat[$k] = -INF;
            if($v === "INF")$dat[$k] = INF;
        }*/
        return new Vector3((float)$dat[0], (float)$dat[1], (float)$dat[2]);
    }

    public static function toString(Vector3 $vec): string
    {
        return "$vec->x:$vec->y:$vec->z";
    }

    #[Pure] public static function fromBlockPosition(BlockPosition $blockPosition): Vector3
    {
        return new Vector3($blockPosition->getX(), $blockPosition->getY(), $blockPosition->getZ());
    }

    public static function setComponents(Vector3 $vector3, ?float $x = null, ?float $y = null, ?float $z = null): Vector3
    {
        $c = clone $vector3;
        if ($x !== null) $c->x = $x;
        if ($y !== null) $c->y = $y;
        if ($z !== null) $c->z = $z;
        return $c;
    }
}