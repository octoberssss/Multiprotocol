<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;

class v486SetActorDataPacket extends SetActorDataPacket{

	public static function fromLatest(SetActorDataPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->metadata = $pk->metadata;
		$npk->tick = $pk->tick;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->metadata = $in->getEntityMetadata();
		$this->tick = $in->getUnsignedVarLong();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putEntityMetadata($this->metadata);
		$out->putUnsignedVarLong($this->tick);
	}
}
