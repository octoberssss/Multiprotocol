<?php

namespace MultiVersion\network\proto\v361\packets\types\resourcepacks;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;

class v361ResourcePackInfoEntry extends ResourcePackInfoEntry{

	public static function fromLatest(ResourcePackInfoEntry $entry) : self{
		$uuid = $entry->getPackId();
		$version = $entry->getVersion();
		$sizeBytes = $entry->getSizeBytes();
		$encryptionKey = $entry->getEncryptionKey();
		$subPackName = $entry->getSubPackName();
		$contentId = $entry->getContentId();
		$hasScripts = $entry->hasScripts();
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, false);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->getPackId());
		$out->putString($this->getVersion());
		$out->putLLong($this->getSizeBytes());
		$out->putString($this->getEncryptionKey() ?? "");
		$out->putString($this->getSubPackName() ?? "");
		$out->putString($this->getContentId() ?? "");
		$out->putBool($this->hasScripts());
	}

	public static function read(PacketSerializer $in) : self{
		$uuid = $in->getString();
		$version = $in->getString();
		$sizeBytes = $in->getLLong();
		$encryptionKey = $in->getString();
		$subPackName = $in->getString();
		$contentId = $in->getString();
		$hasScripts = $in->getBool();
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, false);
	}
}