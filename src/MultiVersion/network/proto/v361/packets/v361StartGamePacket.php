<?php

namespace MultiVersion\network\proto\v361\packets;

use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;

class v361StartGamePacket extends StartGamePacket{

	public int $seed;
	public int $dimension;
	public int $generator = 1; //default infinite - 0 old, 1 infinite, 2 flat
	public int $worldGamemode;
	public int $difficulty;
	public int $spawnX;
	public int $spawnY;
	public int $spawnZ;
	public bool $hasAchievementsDisabled = true;
	public int $time = -1;
	public bool $eduMode = false;
	public bool $hasEduFeaturesEnabled = false;
	public float $rainLevel;
	public float $lightningLevel;
	public bool $hasConfirmedPlatformLockedContent = false;
	public bool $isMultiplayerGame = true;
	public bool $hasLANBroadcast = true;
	public int $xboxLiveBroadcastMode = 0; //TODO: find values
	public int $platformBroadcastMode = 0;
	public bool $commandsEnabled;
	public bool $isTexturePacksRequired = true;
	/**
	 * @var GameRule[]
	 * @phpstan-var array<string, GameRule>
	 */
	public array $gameRules = [];
	public bool $hasBonusChestEnabled = false;
	public bool $hasStartWithMapEnabled = false;
	public int $defaultPlayerPermission = PlayerPermissions::MEMBER; //TODO

	public int $serverChunkTickRadius = 4; //TODO (leave as default for now)

	public bool $hasLockedBehaviorPack = false;
	public bool $hasLockedResourcePack = false;
	public bool $isFromLockedWorldTemplate = false;
	public bool $useMsaGamertagsOnly = false;
	public bool $isFromWorldTemplate = false;
	public bool $isWorldTemplateOptionLocked = false;
	public bool $onlySpawnV1Villagers = false;

	public array $blockTable;
	public array $itemTable;

	public static function fromLatest(StartGamePacket $pk, IRuntimeBlockMapping $blockMapping, array $itemMapping) : self{
		$npk = new self();
		$npk->actorUniqueId = $pk->actorUniqueId;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->playerGamemode = $pk->playerGamemode;
		$npk->playerPosition = $pk->playerPosition;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->seed = $pk->levelSettings->seed;
		$npk->dimension = $pk->levelSettings->spawnSettings->getDimension();
		$npk->worldGamemode = $pk->levelSettings->worldGamemode;
		$npk->difficulty = $pk->levelSettings->difficulty;
		$npk->spawnX = $pk->levelSettings->spawnPosition->getX();
		$npk->spawnY = $pk->levelSettings->spawnPosition->getY();
		$npk->spawnZ = $pk->levelSettings->spawnPosition->getZ();
		$npk->hasAchievementsDisabled = false;
		$npk->time = $pk->levelSettings->time;
		$npk->rainLevel = $pk->levelSettings->rainLevel; //TODO: implement these properly
		$npk->lightningLevel = $pk->levelSettings->lightningLevel;
		$npk->commandsEnabled = true;
		$npk->gameRules = $pk->levelSettings->gameRules;
		$npk->levelId = "";
		$npk->worldName = $pk->worldName;
		$npk->blockTable = $blockCache ?? ($blockCache = $blockMapping->getBedrockKnownStates());
		$npk->itemTable = $itemCache ?? ($itemCache = $itemMapping);
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->playerGamemode = $in->getVarInt();

		$this->playerPosition = $in->getVector3();

		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();

		//Level settings
		$this->seed = $in->getVarInt();
		$this->dimension = $in->getVarInt();
		$this->generator = $in->getVarInt();
		$this->worldGamemode = $in->getVarInt();
		$this->difficulty = $in->getVarInt();
		$in->getBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
		$this->hasAchievementsDisabled = $in->getBool();
		$this->time = $in->getVarInt();
		$this->eduMode = $in->getBool();
		$this->hasEduFeaturesEnabled = $in->getBool();
		$this->rainLevel = $in->getLFloat();
		$this->lightningLevel = $in->getLFloat();
		$this->hasConfirmedPlatformLockedContent = $in->getBool();
		$this->isMultiplayerGame = $in->getBool();
		$this->hasLANBroadcast = $in->getBool();
		$this->xboxLiveBroadcastMode = $in->getVarInt();
		$this->platformBroadcastMode = $in->getVarInt();
		$this->commandsEnabled = $in->getBool();
		$this->isTexturePacksRequired = $in->getBool();
		$this->gameRules = $in->getGameRules();
		$this->hasBonusChestEnabled = $in->getBool();
		$this->hasStartWithMapEnabled = $in->getBool();
		$this->defaultPlayerPermission = $in->getVarInt();
		$this->serverChunkTickRadius = $in->getLInt();
		$this->hasLockedBehaviorPack = $in->getBool();
		$this->hasLockedResourcePack = $in->getBool();
		$this->isFromLockedWorldTemplate = $in->getBool();
		$this->useMsaGamertagsOnly = $in->getBool();
		$this->isFromWorldTemplate = $in->getBool();
		$this->isWorldTemplateOptionLocked = $in->getBool();
		$this->onlySpawnV1Villagers = $in->getBool();

		$this->levelId = $in->getString();
		$this->worldName = $in->getString();
		$this->premiumWorldTemplateId = $in->getString();
		$this->isTrial = $in->getBool();
		$this->currentTick = $in->getLLong();

		$this->enchantmentSeed = $in->getVarInt();

		$this->blockTable = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$id = $in->getString();
			$data = $in->getSignedLShort();
			$unknown = $in->getSignedLShort();

			$this->blockTable[$i] = ["name" => $id, "data" => $data, "legacy_id" => $unknown];
		}
		$this->itemTable = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$id = $in->getString();
			$legacyId = $in->getSignedLShort();

			$this->itemTable[$id] = $legacyId;
		}

		$this->multiplayerCorrelationId = $in->getString();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVarInt($this->playerGamemode);

		$out->putVector3($this->playerPosition);

		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);

		//Level settings
		$out->putVarInt($this->seed);
		$out->putVarInt($this->dimension);
		$out->putVarInt($this->generator);
		$out->putVarInt($this->worldGamemode);
		$out->putVarInt($this->difficulty);
		$out->putBlockPosition(new BlockPosition($this->spawnX, $this->spawnY, $this->spawnZ));
		$out->putBool($this->hasAchievementsDisabled);
		$out->putVarInt($this->time);
		$out->putBool($this->eduMode);
		$out->putBool($this->hasEduFeaturesEnabled);
		$out->putLFloat($this->rainLevel);
		$out->putLFloat($this->lightningLevel);
		$out->putBool($this->hasConfirmedPlatformLockedContent);
		$out->putBool($this->isMultiplayerGame);
		$out->putBool($this->hasLANBroadcast);
		$out->putVarInt($this->xboxLiveBroadcastMode);
		$out->putVarInt($this->platformBroadcastMode);
		$out->putBool($this->commandsEnabled);
		$out->putBool($this->isTexturePacksRequired);
		$out->putGameRules($this->gameRules);
		$out->putBool($this->hasBonusChestEnabled);
		$out->putBool($this->hasStartWithMapEnabled);
		$out->putVarInt($this->defaultPlayerPermission);
		$out->putLInt($this->serverChunkTickRadius);
		$out->putBool($this->hasLockedBehaviorPack);
		$out->putBool($this->hasLockedResourcePack);
		$out->putBool($this->isFromLockedWorldTemplate);
		$out->putBool($this->useMsaGamertagsOnly);
		$out->putBool($this->isFromWorldTemplate);
		$out->putBool($this->isWorldTemplateOptionLocked);
		$out->putBool($this->onlySpawnV1Villagers);

		$out->putString($this->levelId);
		$out->putString($this->worldName);
		$out->putString($this->premiumWorldTemplateId);
		$out->putBool($this->isTrial);
		$out->putLLong($this->currentTick);

		$out->putVarInt($this->enchantmentSeed);

		$out->putUnsignedVarInt(count($this->blockTable));
		foreach($this->blockTable as $v){
			$out->putString($v["name"]);
			$out->putLShort($v["data"]);
			$out->putLShort($v["legacy_id"]);
		}

		$out->putUnsignedVarInt(count($this->itemTable));
		foreach($this->itemTable as $name => $legacyId){
			$out->putString($name);
			$out->putLShort($legacyId);
		}

		$out->putString($this->multiplayerCorrelationId);
	}
}
