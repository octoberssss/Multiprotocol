<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486ModalFormResponsePacket extends ModalFormResponsePacket{

	protected function decodePayload(PacketSerializer $in) : void{
		$this->formId = $in->getUnsignedVarInt();
		$this->formData = $in->getString();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt($this->formId);
		$out->putString($this->formData);
	}
}
