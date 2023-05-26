<?php


namespace CortexPE\std\math;


use pocketmine\math\Vector3;

class BezierCurve
{
    /** @var Vector3[] */
    private $points;

    /**
     * BezierCurve constructor.
     * @param Vector3[] $points
     */
    public function __construct(array $points = [])
    {
        $this->points = $points;
    }

    public function getPoint(float $step): Vector3
    {
        // https://stackoverflow.com/a/21642962
        $tmp = $this->points;
        $i = count($this->points) - 1;
        while ($i > 0) {
            for ($k = 0; $k < $i; $k++) {
                $tmp[$k] = $tmp[$k]->addVector($tmp[$k + 1]->subtractVector($tmp[$k])->multiply($step));
            }
            $i--;
        }
        return $tmp[0];
    }

    public function addPoint(Vector3 $vec3): void
    {
        $this->points[] = $vec3;
    }
}