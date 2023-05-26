<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361CommandRequestPacket extends CommandRequestPacket{

	public static function fromLatest(CommandRequestPacket $pk) : self{
		$npk = new self();
		$npk->command = $pk->command;
		$npk->originData = $pk->originData;
		$npk->isInternal = $pk->isInternal;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->command = $in->getString();
		$this->originData = $in->getCommandOriginData();
		$this->isInternal = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putString($this->command);
		$out->putCommandOriginData($this->originData);
		$out->putBool($this->isInternal);
	}
}
