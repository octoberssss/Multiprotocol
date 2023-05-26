<?php

namespace MultiVersion\network\proto\static_resources;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\data\bedrock\LegacyBlockIdToStringIdMap;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\R12ToCurrentBlockMapEntry;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\utils\AssumptionFailedError;
use RuntimeException;

class GenericRuntimeBlockMapping implements IRuntimeBlockMapping{

	/** @var int[] */
	private array $legacyToRuntimeMap = [];
	/** @var int[] */
	private array $runtimeToLegacyMap = [];
	/** @var CompoundTag[] */
	private array $bedrockKnownStates;

	public function __construct(string $canonicalBlockStatesFilePath, string $r12toCurrentBlockMapFilePath){
		$canonicalBlockStatesFile = file_get_contents($canonicalBlockStatesFilePath);
		if($canonicalBlockStatesFile === false){
			throw new AssumptionFailedError("Missing required resource file");
		}
		$stream = PacketSerializer::decoder($canonicalBlockStatesFile, 0, new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
		$list = [];
		while(!$stream->feof()){
			$list[] = $stream->getNbtCompoundRoot();
		}
		$this->bedrockKnownStates = $list;

		$this->setupLegacyMappings($r12toCurrentBlockMapFilePath);
	}

	private function setupLegacyMappings(string $r12toCurrentBlockMapFilePath) : void{
		$legacyIdMap = LegacyBlockIdToStringIdMap::getInstance();
		/** @var R12ToCurrentBlockMapEntry[] $legacyStateMap */
		$legacyStateMap = [];
		$legacyStateMapReader = PacketSerializer::decoder(file_get_contents($r12toCurrentBlockMapFilePath), 0, new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
		$nbtReader = new NetworkNbtSerializer();
		while(!$legacyStateMapReader->feof()){
			$id = $legacyStateMapReader->getString();
			$meta = $legacyStateMapReader->getLShort();

			$offset = $legacyStateMapReader->getOffset();
			$state = $nbtReader->read($legacyStateMapReader->getBuffer(), $offset)->mustGetCompoundTag();
			$legacyStateMapReader->setOffset($offset);
			$legacyStateMap[] = new R12ToCurrentBlockMapEntry($id, $meta, $state);
		}

		/**
		 * @var int[][] $idToStatesMap string id -> int[] list of candidate state indices
		 */
		$idToStatesMap = [];
		foreach($this->bedrockKnownStates as $k => $state){
			$idToStatesMap[$state->getString("name")][] = $k;
		}
		foreach($legacyStateMap as $pair){
			$id = $legacyIdMap->stringToLegacy($pair->getId());
			if($id === null){
				throw new RuntimeException("No legacy ID matches " . $pair->getId());
			}
			$data = $pair->getMeta();
			if($data > 15){
				//we can't handle metadata with more than 4 bits
				continue;
			}
			$mappedState = $pair->getBlockState();
			$mappedName = $mappedState->getString("name");
			if(!isset($idToStatesMap[$mappedName])){
				throw new RuntimeException("Mapped new state does not appear in network table");
			}
			foreach($idToStatesMap[$mappedName] as $k){
				$networkState = $this->bedrockKnownStates[$k];
				if($mappedState->equals($networkState)){
					$this->registerMapping($k, $id, $data);
					continue 2;
				}
			}
			throw new RuntimeException("Mapped new state does not appear in network table");
		}
	}

	private function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void{
		$this->legacyToRuntimeMap[($legacyId << Block::INTERNAL_METADATA_BITS) | $legacyMeta] = $staticRuntimeId;
		$this->runtimeToLegacyMap[$staticRuntimeId] = ($legacyId << Block::INTERNAL_METADATA_BITS) | $legacyMeta;
	}

	public function toRuntimeId(int $id, int $meta = 0) : int{
		return $this->legacyToRuntimeMap[self::toFullId($id, $meta)] ?? $this->legacyToRuntimeMap[self::toFullId(BlockLegacyIds::INFO_UPDATE)];
	}

	public static function toFullId(int $id, int $meta = 0) : int{
		return ($id << Block::INTERNAL_METADATA_BITS) | $meta;
	}

	public function fromRuntimeId(int $runtimeId) : array{
		$v = $this->runtimeToLegacyMap[$runtimeId];
		return [$v >> 4, $v & 0xf];
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getBedrockKnownStates() : array{
		return $this->bedrockKnownStates;
	}
}