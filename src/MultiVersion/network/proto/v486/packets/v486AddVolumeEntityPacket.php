<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v486\packets;

use CortexPE\std\ReflectionUtils;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v486AddVolumeEntityPacket extends AddVolumeEntityPacket{

	public static function fromLatest(AddVolumeEntityPacket $pk) : self{
		$npk = new self();
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "entityNetId", $pk->getEntityNetId());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "data", $pk->getData());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "jsonIdentifier", $pk->getJsonIdentifier());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "instanceName", $pk->getInstanceName());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "engineVersion", $pk->getEngineVersion());
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "entityNetId", $in->getUnsignedVarInt());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "data", new CacheableNbt($in->getNbtCompoundRoot()));
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "jsonIdentifier", $in->getString());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "instanceName", $in->getString());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "engineVersion", $in->getString());
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt($this->getEntityNetId());
		$out->put($this->getData()->getEncodedNbt());
		$out->putString($this->getJsonIdentifier());
		$out->putString($this->getInstanceName());
		$out->putString($this->getEngineVersion());
	}

}