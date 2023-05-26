<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v361ActorFallPacket extends DataPacket implements ServerboundPacket{

	public const NETWORK_ID = 0x25;

	public int $actorRuntimeId;
	public float $fallDistance;
	public bool $isInVoid;

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->fallDistance = $in->getLFloat();
		$this->isInVoid = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putLFloat($this->fallDistance);
		$out->putBool($this->isInVoid);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true; // this isn't handled anymore, it is useless
	}
}