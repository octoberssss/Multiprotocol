<?php


declare(strict_types=1);

namespace CortexPE\std\math;


final class ColorUtils
{
    private function __construct()
    {
    }

    /**
     * @param float $h 0-1 Hue value (0-360)
     * @param float $s 0-1 Saturation value
     * @param float $l 0-1 Luminance Value
     * @return float[] RGB colors
     */
    public static function hsl2rgb(float $h, float $s, float $l)
    {
        // https://stackoverflow.com/a/20440417

        if ($h > 1 || $h < 0) throw new \UnexpectedValueException("Hue value is outside of allowed range [0 - 1]");
        if ($s > 1 || $s < 0) throw new \UnexpectedValueException("Saturation value is outside of allowed range [0 - 1]");
        if ($l > 1 || $l < 0) throw new \UnexpectedValueException("Luminance value is outside of allowed range [0 - 1]");

        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);

        if ($v > 0) {
            $m = $l + $l - $v;
            $sv = ($v - $m) / $v;
            $h *= 6.0;
            $sextant = (int)floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;

            if ($sextant === 0) {
                $r = $v;
                $g = $mid1;
                $b = $m;
            } elseif ($sextant === 1) {
                $r = $mid2;
                $g = $v;
                $b = $m;
            } elseif ($sextant === 2) {
                $r = $m;
                $g = $v;
                $b = $mid1;
            } elseif ($sextant === 3) {
                $r = $m;
                $g = $mid2;
                $b = $v;
            } elseif ($sextant === 4) {
                $r = $mid1;
                $g = $m;
                $b = $v;
            } elseif ($sextant === 5) {
                $r = $v;
                $g = $m;
                $b = $mid2;
            }
        }
        return ["r" => $r * 255.0, "g" => $g * 255.0, "b" => $b * 255.0];
    }
}