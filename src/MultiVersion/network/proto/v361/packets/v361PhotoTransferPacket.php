<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\PhotoTransferPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361PhotoTransferPacket extends PhotoTransferPacket{

	public static function fromLatest(PhotoTransferPacket $pk) : self{
		$npk = new self();
		$npk->photoName = $pk->photoName;
		$npk->photoData = $pk->photoData;
		$npk->bookId = $pk->bookId;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->photoName = $in->getString();
		$this->photoData = $in->getString();
		$this->bookId = $in->getString();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putString($this->photoName);
		$out->putString($this->photoData);
		$out->putString($this->bookId);
	}
}
