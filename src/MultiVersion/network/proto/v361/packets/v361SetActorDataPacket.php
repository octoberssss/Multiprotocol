<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;

class v361SetActorDataPacket extends SetActorDataPacket{

	public static function fromLatest(SetActorDataPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->metadata = $pk->metadata;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->metadata = $in->getEntityMetadata();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putEntityMetadata($this->metadata);
	}
}