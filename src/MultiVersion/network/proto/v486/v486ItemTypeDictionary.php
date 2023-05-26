<?php

namespace MultiVersion\network\proto\v486;

use MultiVersion\Main;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use Webmozart\PathUtil\Path;

final class v486ItemTypeDictionary{
	use SingletonTrait;

	private ItemTypeDictionary $dictionary;

	public function __construct(ItemTypeDictionary $dictionary){
		$this->dictionary = $dictionary;
	}

	private static function make() : self{
		$data = file_get_contents(Path::join(Main::$resourcePath, "v486", "required_item_list.json"));
		if($data === false) throw new AssumptionFailedError("Missing required resource file");
		$table = json_decode($data, true);
		if(!is_array($table)){
			throw new AssumptionFailedError("Invalid item list format");
		}

		$params = [];
		foreach($table as $name => $entry){
			if(!is_array($entry) || !is_string($name) || !isset($entry["component_based"], $entry["runtime_id"]) || !is_bool($entry["component_based"]) || !is_int($entry["runtime_id"])){
				throw new AssumptionFailedError("Invalid item list format");
			}
			$params[] = new ItemTypeEntry($name, $entry["runtime_id"], $entry["component_based"]);
		}
		return new self(new ItemTypeDictionary($params));
	}

	public function getDictionary() : ItemTypeDictionary{
		return $this->dictionary;
	}
}