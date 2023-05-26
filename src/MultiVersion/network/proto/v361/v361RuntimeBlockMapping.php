<?php

namespace MultiVersion\network\proto\v361;

use MultiVersion\Main;
use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\block\BlockLegacyIds;
use Webmozart\PathUtil\Path;

class v361RuntimeBlockMapping implements IRuntimeBlockMapping{

	/** @var int[] */
	private array $legacyToRuntimeMap = [];
	/** @var int[] */
	private array $runtimeToLegacyMap = [];
	/** @var array|null */
	private ?array $bedrockKnownStates;

	public function __construct(){
		$legacyIdMap = json_decode(file_get_contents(Path::join(Main::$resourcePath, "v361", "block_id_map.json")), true);

		$compressedTable = json_decode(file_get_contents(Path::join(Main::$resourcePath, "v361", "required_block_states.json")), true);
		$decompressed = [];

		foreach($compressedTable as $prefix => $entries){
			foreach($entries as $shortStringId => $states){
				foreach($states as $state){
					$name = "$prefix:$shortStringId";
					$decompressed[] = [
						"name" => $name,
						"data" => $state,
						"legacy_id" => $legacyIdMap[$name]
					];
				}
			}
		}
		$this->bedrockKnownStates = $decompressed;//self::randomizeTable($decompressed);

		foreach($this->bedrockKnownStates as $k => $obj){
			if($obj["data"] > 15){
				//TODO: in 1.12 they started using data values bigger than 4 bits which we can't handle right now
				continue;
			}
			//this has to use the json offset to make sure the mapping is consistent with what we send over network, even though we aren't using all the entries
			self::registerMapping($k, $obj["legacy_id"], $obj["data"]);
		}
	}

	private function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void{
		$this->legacyToRuntimeMap[($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
		$this->runtimeToLegacyMap[$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
	}

	/**
	 * Randomizes the order of the runtimeID table to prevent plugins relying on them.
	 * Plugins shouldn't use this stuff anyway, but plugin devs have an irritating habit of ignoring what they
	 * aren't supposed to do, so we have to deliberately break it to make them stop.
	 *
	 * @param array $table
	 *
	 * @return array
	 */
	private static function randomizeTable(array $table) : array{
		$postSeed = mt_rand(); //save a seed to set afterwards, to avoid poor quality randoms
		mt_srand(getmypid() ?: 0); //Use a seed which is the same on all threads. This isn't a secure seed, but we don't care.
		shuffle($table);
		mt_srand($postSeed); //restore a good quality seed that isn't dependent on PID
		return $table;
	}

	/**
	 * @param int $id
	 * @param int $meta
	 *
	 * @return int
	 */
	public function toRuntimeId(int $id, int $meta = 0) : int{
		/*
		 * try id+meta first
		 * if not found, try id+0 (strip meta)
		 * if still not found, return update! block
		 */
		return $this->legacyToRuntimeMap[($id << 4) | $meta] ?? $this->legacyToRuntimeMap[$id << 4] ?? $this->legacyToRuntimeMap[BlockLegacyIds::INFO_UPDATE << 4];
	}

	/**
	 * @param int $runtimeId
	 *
	 * @return int[] [id, meta]
	 */
	public function fromRuntimeId(int $runtimeId) : array{
		$v = $this->runtimeToLegacyMap[$runtimeId];
		return [$v >> 4, $v & 0xf];
	}

	/**
	 * @return array
	 */
	public function getBedrockKnownStates() : array{
		return $this->bedrockKnownStates;
	}
}
