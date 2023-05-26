<?php

namespace MultiVersion\network\proto\v486\packets;

use MultiVersion\network\proto\v486\packets\types\v486LevelSettings;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;

class v486StartGamePacket extends StartGamePacket{

	public v486LevelSettings $_levelSettings;

	public static function fromLatest(StartGamePacket $packet) : self{
		$npk = new self();
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->playerGamemode = $packet->playerGamemode;
		$npk->playerPosition = $packet->playerPosition;
		$npk->pitch = $packet->pitch;
		$npk->yaw = $packet->yaw;
		$npk->_levelSettings = v486LevelSettings::fromLatest($packet->levelSettings);
		$npk->levelId = $packet->levelId;
		$npk->worldName = $packet->worldName;
		$npk->premiumWorldTemplateId = $packet->premiumWorldTemplateId;
		$npk->isTrial = $packet->isTrial;
		$npk->playerMovementSettings = $packet->playerMovementSettings;
		$npk->currentTick = $packet->currentTick;
		$npk->enchantmentSeed = $packet->enchantmentSeed;
		$npk->blockPalette = $packet->blockPalette;
		$npk->itemTable = $packet->itemTable;
		$npk->multiplayerCorrelationId = $packet->multiplayerCorrelationId;
		$npk->enableNewInventorySystem = $packet->enableNewInventorySystem;
		$npk->serverSoftwareVersion = $packet->serverSoftwareVersion;
		$npk->blockPaletteChecksum = $packet->blockPaletteChecksum;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->playerGamemode = $in->getVarInt();

		$this->playerPosition = $in->getVector3();

		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();

		$this->_levelSettings = v486LevelSettings::read($in);

		$this->levelId = $in->getString();
		$this->worldName = $in->getString();
		$this->premiumWorldTemplateId = $in->getString();
		$this->isTrial = $in->getBool();
		$this->playerMovementSettings = PlayerMovementSettings::read($in);
		$this->currentTick = $in->getLLong();

		$this->enchantmentSeed = $in->getVarInt();

		$this->blockPalette = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$blockName = $in->getString();
			$state = $in->getNbtCompoundRoot();
			$this->blockPalette[] = new BlockPaletteEntry($blockName, new CacheableNbt($state));
		}

		$this->itemTable = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$stringId = $in->getString();
			$numericId = $in->getSignedLShort();
			$isComponentBased = $in->getBool();

			$this->itemTable[] = new ItemTypeEntry($stringId, $numericId, $isComponentBased);
		}

		$this->multiplayerCorrelationId = $in->getString();
		$this->enableNewInventorySystem = $in->getBool();
		$this->serverSoftwareVersion = $in->getString();
		$this->blockPaletteChecksum = $in->getLLong();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVarInt($this->playerGamemode);

		$out->putVector3($this->playerPosition);

		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);

		$this->_levelSettings->write($out);

		$out->putString($this->levelId);
		$out->putString($this->worldName);
		$out->putString($this->premiumWorldTemplateId);
		$out->putBool($this->isTrial);
		$this->playerMovementSettings->write($out);
		$out->putLLong($this->currentTick);

		$out->putVarInt($this->enchantmentSeed);

		$out->putUnsignedVarInt(count($this->blockPalette));
		foreach($this->blockPalette as $entry){
			$out->putString($entry->getName());
			$out->put($entry->getStates()->getEncodedNbt());
		}

		$out->putUnsignedVarInt(count($this->itemTable));
		foreach($this->itemTable as $entry){
			$out->putString($entry->getStringId());
			$out->putLShort($entry->getNumericId());
			$out->putBool($entry->isComponentBased());
		}

		$out->putString($this->multiplayerCorrelationId);
		$out->putBool($this->enableNewInventorySystem);
		$out->putString($this->serverSoftwareVersion);
		$out->putLLong($this->blockPaletteChecksum);
	}
}