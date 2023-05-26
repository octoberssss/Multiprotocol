<?php

namespace MultiVersion\network\proto\static_resources;

use pocketmine\item\Durable;
use pocketmine\item\Item;

class GenericCreativeInventory{

	/** @var Item[] */
	private array $creative;

	public function __construct(string $creativeItemsPath){
		$creativeItems = json_decode(file_get_contents($creativeItemsPath), true);

		foreach($creativeItems as $data){
			$item = Item::jsonDeserialize($data);
			if($item->getName() === "Unknown"){
				continue;
			}
			$this->add($item);
		}
	}

	/**
	 * Adds an item to the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function add(Item $item) : void{
		$this->creative[] = clone $item;
	}

	/**
	 * Removes all previously added items from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function clear() : void{
		$this->creative = [];
	}

	/**
	 * @return Item[]
	 */
	public function getAll() : array{
		return $this->creative;
	}

	public function getItem(int $index) : ?Item{
		return $this->creative[$index] ?? null;
	}

	/**
	 * Removes an item from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function remove(Item $item) : void{
		$index = $this->getItemIndex($item);
		if($index !== -1){
			unset($this->creative[$index]);
		}
	}

	public function getItemIndex(Item $item) : int{
		foreach($this->creative as $i => $d){
			if($item->equals($d, !($item instanceof Durable))){
				return $i;
			}
		}

		return -1;
	}

	public function contains(Item $item) : bool{
		return $this->getItemIndex($item) !== -1;
	}
}