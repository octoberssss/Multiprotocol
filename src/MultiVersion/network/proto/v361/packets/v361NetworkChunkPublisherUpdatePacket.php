<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;

class v361NetworkChunkPublisherUpdatePacket extends NetworkChunkPublisherUpdatePacket{

	public static function fromLatest(NetworkChunkPublisherUpdatePacket $pk) : self{
		$npk = new self();
		$npk->blockPosition = $pk->blockPosition;
		$npk->radius = $pk->radius;
        $npk->savedChunks = $pk->savedChunks;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->blockPosition = $in->getSignedBlockPosition();
		$this->radius = $in->getUnsignedVarInt();

		for($i = 0, $this->savedChunks = [], $count = $in->getLInt(); $i < $count; $i++){
			$this->savedChunks[] = ChunkPosition::read($in);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putSignedBlockPosition($this->blockPosition);
		$out->putUnsignedVarInt($this->radius);

		$out->putLInt(count($this->savedChunks));
		foreach($this->savedChunks as $chunk){
			$chunk->write($out);
		}
	}
}
