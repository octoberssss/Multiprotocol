<?php

namespace MultiVersion\network\proto\latest;

use Exception;
use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\block\BlockLegacyIds;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;

class LatestRuntimeBlockMappingWrapper implements IRuntimeBlockMapping{

	private RuntimeBlockMapping $encapsulated;

	public function __construct(){
		$this->encapsulated = RuntimeBlockMapping::getInstance();
	}

	public function toRuntimeId(int $id, int $meta = 0) : int{
		return $this->encapsulated->toRuntimeId(($id << 4) | $meta);
	}

	public function fromRuntimeId(int $runtimeId) : array{
		try{
			$fullID = $this->encapsulated->fromRuntimeId($runtimeId);
		}catch(Exception){
			$fullID = BlockLegacyIds::INFO_UPDATE << 4;
		}
		return [$fullID >> 4, $fullID & 0xf];
	}

	public function getBedrockKnownStates() : array{
		return $this->encapsulated->getBedrockKnownStates();
	}
}