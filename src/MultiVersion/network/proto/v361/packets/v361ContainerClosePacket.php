<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361ContainerClosePacket extends ContainerClosePacket{

	public static function fromLatest(ContainerClosePacket $pk) : self{
		$npk = new self();
		$npk->windowId = $pk->windowId;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->windowId = $in->getByte();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putByte($this->windowId);
	}
}