<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361ResourcePackChunkDataPacket extends ResourcePackChunkDataPacket{

	public static function fromLatest(ResourcePackChunkDataPacket $pk) : self{
		$npk = new self();
		$npk->packId = $pk->packId;
		$npk->chunkIndex = $pk->chunkIndex;
		$npk->offset = $pk->offset;
		$npk->data = $pk->data;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->packId = $in->getString();
		$this->chunkIndex = $in->getLInt();
		$this->offset = $in->getLLong();
		$this->data = $in->get($in->getLInt());
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putString($this->packId);
		$out->putLInt($this->chunkIndex);
		$out->putLLong($this->offset);
		$out->putLInt(strlen($this->data));
		$out->put($this->data);
	}
}