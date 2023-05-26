<?php


namespace CortexPE\std;


use pocketmine\entity\Skin;

final class SkinUtils
{
    public static function grayscaleSkin(Skin $skin): Skin
    {
        $data = $skin->getSkinData();
        $newData = "";
        for ($i = 0; $i < strlen($data); $i += 4) {
            $r = ord($data[$i]);
            $g = ord($data[$i + 1]);
            $b = ord($data[$i + 2]);
            $c = chr(floor(($r + $g + $b) / 3));
            $newData .= $c . $c . $c . $data[$i + 3];
        }
        return new Skin($skin->getSkinId(), $newData, $skin->getCapeData(), $skin->getGeometryName(), $skin->getGeometryData());
    }
}