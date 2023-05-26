<?php


namespace CortexPE\std;


use pocketmine\block\Block;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\world\World;

final class AABBUtils
{
    private function __construct()
    {
    }

    public static function midpoint(AxisAlignedBB $aaBB): Vector3
    {
        return new Vector3(($aaBB->maxX + $aaBB->minX) / 2, ($aaBB->maxY + $aaBB->minY) / 2, ($aaBB->maxZ + $aaBB->minZ) / 2);
    }

    public static function min(AxisAlignedBB $aaBB): Vector3
    {
        return new Vector3($aaBB->minX, $aaBB->minY, $aaBB->minZ);
    }

    public static function max(AxisAlignedBB $aaBB): Vector3
    {
        return new Vector3($aaBB->maxX, $aaBB->maxY, $aaBB->maxZ);
    }

    public static function fromCoordinates(Vector3 ...$positions): AxisAlignedBB
    {
        $minX = PHP_INT_MAX;
        $maxX = PHP_INT_MIN;
        $minY = PHP_INT_MAX;
        $maxY = PHP_INT_MIN;
        $minZ = PHP_INT_MAX;
        $maxZ = PHP_INT_MIN;
        foreach ($positions as $pos) {
            if ($pos->x < $minX) $minX = $pos->x;
            if ($pos->x > $maxX) $maxX = $pos->x;
            if ($pos->y < $minY) $minY = $pos->y;
            if ($pos->y > $maxY) $maxY = $pos->y;
            if ($pos->z < $minZ) $minZ = $pos->z;
            if ($pos->z > $maxZ) $maxZ = $pos->z;
        }
        return new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
    }

    public static function collidesWithBlock(World $world, AxisAlignedBB $bb, ?Block &$block = null): bool
    {
        $min = (new Vector3($bb->minX, $bb->minY, $bb->minZ))->floor();
        $max = (new Vector3($bb->maxX, $bb->maxY, $bb->maxZ))->floor();

        for ($x = $min->x; $x <= $max->x; ++$x) {
            for ($z = $min->z; $z <= $max->z; ++$z) {
                for ($y = $min->y; $y <= $max->y; ++$y) {
                    $block = $world->getBlockAt($x, $y, $z);
                    foreach ($block->getCollisionBoxes() as $blockBB) {
                        if ($blockBB->intersectsWith($bb)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}