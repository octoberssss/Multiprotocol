<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486NetworkChunkPublisherUpdatePacket extends NetworkChunkPublisherUpdatePacket{

	public static function fromLatest(NetworkChunkPublisherUpdatePacket $packet) : self{
		$result = new self;
		$result->blockPosition = $packet->blockPosition;
		$result->radius = $packet->radius;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->blockPosition = $in->getSignedBlockPosition();
		$this->radius = $in->getUnsignedVarInt();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putSignedBlockPosition($this->blockPosition);
		$out->putUnsignedVarInt($this->radius);
	}
}