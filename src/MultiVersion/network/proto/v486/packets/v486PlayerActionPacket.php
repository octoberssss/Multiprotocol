<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486PlayerActionPacket extends PlayerActionPacket{

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->action = $in->getVarInt();
		$this->blockPosition = $in->getBlockPosition();
		$this->face = $in->getVarInt();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVarInt($this->action);
		$out->putBlockPosition($this->blockPosition);
		$out->putVarInt($this->face);
	}
}
