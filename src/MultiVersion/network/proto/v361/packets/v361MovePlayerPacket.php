<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361MovePlayerPacket extends MovePlayerPacket{

	public static function fromLatest(MovePlayerPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->position = $pk->position;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->headYaw = $pk->headYaw;
		$npk->mode = $pk->mode;
		$npk->onGround = $pk->onGround;
		$npk->ridingActorRuntimeId = $pk->ridingActorRuntimeId;
		$npk->teleportCause = $pk->teleportCause;
		$npk->teleportItem = $pk->teleportItem;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->position = $in->getVector3();
		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();
		$this->headYaw = $in->getLFloat();
		$this->mode = $in->getByte();
		$this->onGround = $in->getBool();
		$this->ridingActorRuntimeId = $in->getActorRuntimeId();
		if($this->mode === MovePlayerPacket::MODE_TELEPORT){
			$this->teleportCause = $in->getLInt();
			$this->teleportItem = $in->getLInt();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVector3($this->position);
		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
		$out->putLFloat($this->headYaw); //TODO
		$out->putByte($this->mode);
		$out->putBool($this->onGround);
		$out->putActorRuntimeId($this->ridingActorRuntimeId);
		if($this->mode === MovePlayerPacket::MODE_TELEPORT){
			$out->putLInt($this->teleportCause);
			$out->putLInt($this->teleportItem);
		}
	}
}