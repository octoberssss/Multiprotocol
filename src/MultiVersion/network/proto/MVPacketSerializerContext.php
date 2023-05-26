<?php

namespace MultiVersion\network\proto;

use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;

class MVPacketSerializerContext implements IMVPacketSerializerContext{

	public function __construct(
		private ItemTypeDictionary $itemDictionary,
		private IRuntimeBlockMapping $blockMapping
	){

	}

	public function getItemDictionary() : ItemTypeDictionary{
		return $this->itemDictionary;
	}

	public function getBlockMapping() : IRuntimeBlockMapping{
		return $this->blockMapping;
	}
}