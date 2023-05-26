<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;

class v361SetSpawnPositionPacket extends SetSpawnPositionPacket{

	public bool $spawnForced;

	public static function fromLatest(SetSpawnPositionPacket $pk) : self{
		$npk = new self();
		$npk->spawnType = $pk->spawnType;
		$npk->spawnPosition = $pk->spawnPosition;
		$npk->spawnForced = true;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->spawnType = $in->getVarInt();
		$this->spawnPosition = $in->getBlockPosition();
		$this->spawnForced = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putVarInt($this->spawnType);
		$out->putBlockPosition($this->spawnPosition);
		$out->putBool($this->spawnForced);
	}
}
