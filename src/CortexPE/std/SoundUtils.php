<?php


namespace CortexPE\std;


use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;

final class SoundUtils
{
    private function __construct()
    {
    }

    public static function play(Player $p, string $soundName, float $volume = 1, float $pitch = 1): void
    {
        $pk = new PlaySoundPacket();
        $pk->soundName = $soundName;
        $pk->pitch = $pitch;
        $pk->volume = $volume;
        $pos = $p->getEyePos();
        $pk->x = $pos->x;
        $pk->y = $pos->y;
        $pk->z = $pos->z;
        $p->getNetworkSession()->sendDataPacket($pk);
    }
}