<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\HurtArmorPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361HurtArmorPacket extends HurtArmorPacket{

	public static function fromLatest(HurtArmorPacket $pk) : self{
		$npk = new self();
		$npk->health = $pk->health;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->health = $in->getVarInt();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putVarInt($this->health);
	}
}
