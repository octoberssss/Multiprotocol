<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

class v361SetTitlePacket extends SetTitlePacket{

	public static function fromLatest(SetTitlePacket $pk) : self{
		$npk = new self();
		$npk->type = $pk->type;
		$npk->text = $pk->text;
		$npk->fadeInTime = $pk->fadeInTime;
		$npk->stayTime = $pk->stayTime;
		$npk->fadeOutTime = $pk->fadeOutTime;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->type = $in->getVarInt();
		$this->text = $in->getString();
		$this->fadeInTime = $in->getVarInt();
		$this->stayTime = $in->getVarInt();
		$this->fadeOutTime = $in->getVarInt();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putVarInt($this->type);
		$out->putString($this->text);
		$out->putVarInt($this->fadeInTime);
		$out->putVarInt($this->stayTime);
		$out->putVarInt($this->fadeOutTime);
	}
}
