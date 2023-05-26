<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;

class v361UpdateAttributesPacket extends UpdateAttributesPacket{

	public static function fromLatest(UpdateAttributesPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->entries = $pk->entries;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->entries = $in->getAttributeList();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putAttributeList(...array_values($this->entries));
	}
}