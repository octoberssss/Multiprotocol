<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;

class v361ResourcePackStackPacket extends ResourcePackStackPacket{

	public bool $isExperimental = false;

	public static function fromLatest(ResourcePackStackPacket $pk) : self{
		$npk = new self();
		$npk->mustAccept = $pk->mustAccept;
		$npk->resourcePackStack = $pk->resourcePackStack;
		$npk->behaviorPackStack = $pk->behaviorPackStack;
		$npk->isExperimental = count($pk->experiments->getExperiments()) > 0;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->mustAccept = $in->getBool();
		$behaviorPackCount = $in->getUnsignedVarInt();
		while($behaviorPackCount-- > 0){
			$this->behaviorPackStack[] = ResourcePackStackEntry::read($in);
		}

		$resourcePackCount = $in->getUnsignedVarInt();
		while($resourcePackCount-- > 0){
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in);
		}

		$this->isExperimental = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->mustAccept);

		$out->putUnsignedVarInt(count($this->behaviorPackStack));
		foreach($this->behaviorPackStack as $entry){
			$entry->write($out);
		}

		$out->putUnsignedVarInt(count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
			$entry->write($out);
		}

		$out->putBool($this->isExperimental);
	}
}