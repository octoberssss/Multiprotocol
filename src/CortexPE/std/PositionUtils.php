<?php


namespace CortexPE\std;


use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\WorldException;
use pocketmine\world\WorldManager;

final class PositionUtils
{
    /** @var WorldManager */
    private static $WORLD_MANAGER = null;

    private function __construct()
    {
    }

    public static function fromString(string $data): Position
    {
        $dat = explode(":", $data);
        if (count($dat) !== 4) {
            throw new \UnexpectedValueException("Expected string with format x:y:z. $data given");
        }
        $wName = $dat[3];
        if (self::$WORLD_MANAGER === null) {
            self::$WORLD_MANAGER = Server::getInstance()->getWorldManager();
        }
        if (!self::$WORLD_MANAGER->isWorldLoaded($wName) && !self::$WORLD_MANAGER->loadWorld($wName)) {
            throw new WorldException("Unable to find world: '$wName'");
        }
        return new Position((float)$dat[0], (float)$dat[1], (float)$dat[2], self::$WORLD_MANAGER->getWorldByName($dat[3]));
    }

    public static function toString(Position $pos): string
    {
        return "$pos->x:$pos->y:$pos->z:{$pos->world->getFolderName()}";
    }

    public static function floor(Position $pos): Position
    {
        $c = clone $pos;
        $c->x = (int)floor($pos->x);
        $c->y = (int)floor($pos->y);
        $c->z = (int)floor($pos->z);
        return $c;
    }

    public static function add(Position $pos, float $x, float $y, float $z): Position
    {
        $c = clone $pos;
        $c->x += $x;
        $c->y += $y;
        $c->z += $z;
        return $c;
    }
}