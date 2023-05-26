<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;

class v486SpawnParticleEffectPacket extends SpawnParticleEffectPacket{

	public static function fromLatest(SpawnParticleEffectPacket $packet) : self{
		$npk = new self();
		$npk->dimensionId = $packet->dimensionId;
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->position = $packet->position;
		$npk->particleName = $packet->particleName;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->dimensionId = $in->getByte();
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->position = $in->getVector3();
		$this->particleName = $in->getString();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putByte($this->dimensionId);
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putVector3($this->position);
		$out->putString($this->particleName);
	}
}