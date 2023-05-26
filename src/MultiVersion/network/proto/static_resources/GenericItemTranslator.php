<?php

namespace MultiVersion\network\proto\static_resources;

use InvalidArgumentException;
use pocketmine\data\bedrock\LegacyItemIdToStringIdMap;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class GenericItemTranslator{

	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $simpleCoreToNetMapping = [];
	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $simpleNetToCoreMapping = [];

	/**
	 * runtimeId = array[internalId][metadata]
	 * @var int[][]
	 * @phpstan-var array<int, array<int, int>>
	 */
	private array $complexCoreToNetMapping = [];
	/**
	 * [internalId, metadata] = array[runtimeId]
	 * @var int[][]
	 * @phpstan-var array<int, array{int, int}>
	 */
	private array $complexNetToCoreMapping = [];

	public function __construct(string $r16toCurrentItemMapFilePath){
		$data = Filesystem::fileGetContents($r16toCurrentItemMapFilePath);
		$json = json_decode($data, true);
		if(!is_array($json) || !isset($json["simple"], $json["complex"]) || !is_array($json["simple"]) || !is_array($json["complex"])){
			throw new AssumptionFailedError("Invalid item table format");
		}

		$legacyStringToIntMap = LegacyItemIdToStringIdMap::getInstance();

		/** @phpstan-var array<string, int> $simpleMappings */
		$simpleMappings = [];
		foreach($json["simple"] as $oldId => $newId){
			if(!is_string($oldId) || !is_string($newId)){
				throw new AssumptionFailedError("Invalid item table format");
			}
			$intId = $legacyStringToIntMap->stringToLegacy($oldId);
			if($intId === null){
				//new item without a fixed legacy ID - we can't handle this right now
				continue;
			}
			$simpleMappings[$newId] = $intId;
		}
		foreach(Utils::stringifyKeys($legacyStringToIntMap->getStringToLegacyMap()) as $stringId => $intId){
			if(isset($simpleMappings[$stringId])){
				throw new UnexpectedValueException("Old ID $stringId collides with new ID");
			}
			$simpleMappings[$stringId] = $intId;
		}

		/** @phpstan-var array<string, array{int, int}> $complexMappings */
		$complexMappings = [];
		foreach($json["complex"] as $oldId => $map){
			if(!is_string($oldId) || !is_array($map)){
				throw new AssumptionFailedError("Invalid item table format");
			}
			foreach($map as $meta => $newId){
				if(!is_numeric($meta) || !is_string($newId)){
					throw new AssumptionFailedError("Invalid item table format");
				}
				$intId = $legacyStringToIntMap->stringToLegacy($oldId);
				if($intId === null){
					//new item without a fixed legacy ID - we can't handle this right now
					continue;
				}
				$complexMappings[$newId] = [$intId, (int) $meta];
			}
		}

		$dictionary = GlobalItemTypeDictionary::getInstance()->getDictionary();
		foreach($dictionary->getEntries() as $entry){
			$stringId = $entry->getStringId();
			$netId = $entry->getNumericId();
			if(isset($complexMappings[$stringId])){
				[$id, $meta] = $complexMappings[$stringId];
				$this->complexCoreToNetMapping[$id][$meta] = $netId;
				$this->complexNetToCoreMapping[$netId] = [$id, $meta];
			}elseif(isset($simpleMappings[$stringId])){
				$this->simpleCoreToNetMapping[$simpleMappings[$stringId]] = $netId;
				$this->simpleNetToCoreMapping[$netId] = $simpleMappings[$stringId];
			}else{
				//not all items have a legacy mapping - for now, we only support the ones that do
				continue;
			}
		}
	}

	/**
	 * @return int[]|null
	 * @phpstan-return array{int, int}|null
	 */
	public function toNetworkIdQuiet(int $internalId, int $internalMeta) : ?array{
		if($internalMeta === -1){
			$internalMeta = 0x7fff;
		}
		if(isset($this->complexCoreToNetMapping[$internalId][$internalMeta])){
			return [$this->complexCoreToNetMapping[$internalId][$internalMeta], 0];
		}
		if(array_key_exists($internalId, $this->simpleCoreToNetMapping)){
			return [$this->simpleCoreToNetMapping[$internalId], $internalMeta];
		}

		return null;
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 */
	public function toNetworkId(int $internalId, int $internalMeta) : array{
		return $this->toNetworkIdQuiet($internalId, $internalMeta) ??
			throw new InvalidArgumentException("Unmapped ID/metadata combination $internalId:$internalMeta");
	}

	/**
	 * @phpstan-param-out bool $isComplexMapping
	 * @return int[]
	 * @phpstan-return array{int, int}
	 * @throws TypeConversionException
	 */
	public function fromNetworkId(int $networkId, int $networkMeta, ?bool &$isComplexMapping = null) : array{
		if(isset($this->complexNetToCoreMapping[$networkId])){
			if($networkMeta !== 0){
				throw new TypeConversionException("Unexpected non-zero network meta on complex item mapping");
			}
			$isComplexMapping = true;
			return $this->complexNetToCoreMapping[$networkId];
		}
		$isComplexMapping = false;
		if(isset($this->simpleNetToCoreMapping[$networkId])){
			return [$this->simpleNetToCoreMapping[$networkId], $networkMeta];
		}
		throw new TypeConversionException("Unmapped network ID/metadata combination $networkId:$networkMeta");
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 * @throws TypeConversionException
	 */
	public function fromNetworkIdWithWildcardHandling(int $networkId, int $networkMeta) : array{
		$isComplexMapping = false;
		if($networkMeta !== 0x7fff){
			return $this->fromNetworkId($networkId, $networkMeta);
		}
		[$id, $meta] = $this->fromNetworkId($networkId, 0, $isComplexMapping);
		return [$id, $isComplexMapping ? $meta : -1];
	}
}