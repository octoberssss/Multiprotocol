<?php


namespace CortexPE\std;


use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;

class SimpleCallbackInventoryListener implements InventoryListener
{
    /** @var callable */
    private $onSlotChange;
    /** @var callable */
    private $onContentChange;

    public function __construct(callable $onSlotChange, callable $onContentChange)
    {
        $this->onSlotChange = $onSlotChange;
        $this->onContentChange = $onContentChange;
    }

    public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem): void
    {
        ($this->onSlotChange)($inventory, $slot, $oldItem);
    }

    public function onContentChange(Inventory $inventory, array $oldContents): void
    {
        ($this->onContentChange)($inventory, $oldContents);
    }
}