<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;

class v361SpawnParticleEffectPacket extends SpawnParticleEffectPacket{

	public static function fromLatest(SpawnParticleEffectPacket $pk) : self{
		$npk = new self();
		$npk->dimensionId = $pk->dimensionId;
		$npk->actorUniqueId = $pk->actorUniqueId;
		$npk->position = $pk->position;
		$npk->particleName = $pk->particleName;
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
