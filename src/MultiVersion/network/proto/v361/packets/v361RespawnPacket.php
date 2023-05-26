<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361RespawnPacket extends RespawnPacket{

	public static function fromLatest(RespawnPacket $pk) : self{
		$npk = new self();
		$npk->position = $pk->position;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->position = $in->getVector3();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putVector3($this->position);
	}
}