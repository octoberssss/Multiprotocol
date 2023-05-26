<?php


namespace CortexPE\std;


use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

final class InventoryUtils
{
    private function __construct()
    {
    }

    public static function addItemToSlot(Inventory $inv, int $slot, Item $item): array
    {
        $excess = [];
        $cI = clone $item;
        $mss = $cI->getMaxStackSize();
        for ($s = $slot; $s < $inv->getSize(); $s++) {
            $sI = $inv->getItem($s);
            $sIc = $sI->getCount();
            if (($sIn = $sI->isNull()) || $sIc < $mss) {
                if ($sIn || $sI->equals($cI)) {
                    $canAdd = min($mss - $sIc, $cI->getCount());
                    $cI->setCount($cI->getCount() - $canAdd);

                    $sI = clone $cI;
                    $sI->setCount($canAdd);
                    $inv->setItem($s, $sI);
                }
            }
            if ($cI->isNull()) { // we've already reached 0
                break;
            }
        }
        if (!$cI->isNull()) { // drop it
            $excess[] = $cI;
        }
        return $excess;
    }
}