<?php

namespace MultiVersion\network\proto;

use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;

interface IMVPacketSerializerContext{

	public function getItemDictionary() : ItemTypeDictionary;

	public function getBlockMapping() : IRuntimeBlockMapping;
}