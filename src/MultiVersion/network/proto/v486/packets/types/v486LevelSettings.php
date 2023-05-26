<?php

namespace MultiVersion\network\proto\v486\packets\types;

use Closure;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\ChatRestrictionLevel;
use pocketmine\network\mcpe\protocol\types\EducationEditionOffer;
use pocketmine\network\mcpe\protocol\types\EducationUriResource;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\GeneratorType;
use pocketmine\network\mcpe\protocol\types\LevelSettings;
use pocketmine\network\mcpe\protocol\types\MultiplayerGameVisibility;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\utils\BinaryDataException;

final class v486LevelSettings{

	public int $seed;
	public SpawnSettings $spawnSettings;
	public int $generator = GeneratorType::OVERWORLD;
	public int $worldGamemode;
	public int $difficulty;
	public BlockPosition $spawnPosition;
	public bool $hasAchievementsDisabled = true;
	public bool $isEditorMode = false;
	public int $time = -1;
	public int $eduEditionOffer = EducationEditionOffer::NONE;
	public bool $hasEduFeaturesEnabled = false;
	public string $eduProductUUID = "";
	public float $rainLevel;
	public float $lightningLevel;
	public bool $hasConfirmedPlatformLockedContent = false;
	public bool $isMultiplayerGame = true;
	public bool $hasLANBroadcast = true;
	public int $xboxLiveBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	public int $platformBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	public bool $commandsEnabled;
	public bool $isTexturePacksRequired = true;
	/**
	 * @var GameRule[]
	 * @phpstan-var array<string, GameRule>
	 */
	public array $gameRules = [];
	public Experiments $experiments;
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
	public bool $disablePersona = false;
	public bool $disableCustomSkins = false;
	public string $vanillaVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	public int $limitedWorldWidth = 0;
	public int $limitedWorldLength = 0;
	public bool $isNewNether = true;
	public ?EducationUriResource $eduSharedUriResource = null;
	public ?bool $experimentalGameplayOverride = null;
	public int $chatRestrictionLevel = ChatRestrictionLevel::NONE;
	public bool $disablePlayerInteractions = false;

	public static function fromLatest(LevelSettings $levelSettings) : self{
		$result = new self();
		$result->seed = $levelSettings->seed;
		$result->spawnSettings = $levelSettings->spawnSettings;
		$result->generator = $levelSettings->generator;
		$result->worldGamemode = $levelSettings->worldGamemode;
		$result->difficulty = $levelSettings->difficulty;
		$result->spawnPosition = $levelSettings->spawnPosition;
		$result->hasAchievementsDisabled = $levelSettings->hasAchievementsDisabled;
		$result->time = $levelSettings->time;
		$result->eduEditionOffer = $levelSettings->eduEditionOffer;
		$result->hasEduFeaturesEnabled = $levelSettings->hasEduFeaturesEnabled;
		$result->eduProductUUID = $levelSettings->eduProductUUID;
		$result->rainLevel = $levelSettings->rainLevel;
		$result->lightningLevel = $levelSettings->lightningLevel;
		$result->hasConfirmedPlatformLockedContent = $levelSettings->hasConfirmedPlatformLockedContent;
		$result->isMultiplayerGame = $levelSettings->isMultiplayerGame;
		$result->hasLANBroadcast = $levelSettings->hasLANBroadcast;
		$result->xboxLiveBroadcastMode = $levelSettings->xboxLiveBroadcastMode;
		$result->platformBroadcastMode = $levelSettings->platformBroadcastMode;
		$result->commandsEnabled = $levelSettings->commandsEnabled;
		$result->isTexturePacksRequired = $levelSettings->isTexturePacksRequired;
		$result->gameRules = $levelSettings->gameRules;
		$result->experiments = $levelSettings->experiments;
		$result->hasBonusChestEnabled = $levelSettings->hasBonusChestEnabled;
		$result->hasStartWithMapEnabled = $levelSettings->hasStartWithMapEnabled;
		$result->defaultPlayerPermission = $levelSettings->defaultPlayerPermission;
		$result->serverChunkTickRadius = $levelSettings->serverChunkTickRadius;
		$result->hasLockedBehaviorPack = $levelSettings->hasLockedBehaviorPack;
		$result->hasLockedResourcePack = $levelSettings->hasLockedResourcePack;
		$result->isFromLockedWorldTemplate = $levelSettings->isFromLockedWorldTemplate;
		$result->useMsaGamertagsOnly = $levelSettings->useMsaGamertagsOnly;
		$result->isFromWorldTemplate = $levelSettings->isFromWorldTemplate;
		$result->isWorldTemplateOptionLocked = $levelSettings->isWorldTemplateOptionLocked;
		$result->onlySpawnV1Villagers = $levelSettings->onlySpawnV1Villagers;
		$result->vanillaVersion = $levelSettings->vanillaVersion;
		$result->limitedWorldWidth = $levelSettings->limitedWorldWidth;
		$result->limitedWorldLength = $levelSettings->limitedWorldLength;
		$result->isNewNether = $levelSettings->isNewNether;
		$result->eduSharedUriResource = $levelSettings->eduSharedUriResource;
		$result->experimentalGameplayOverride = $levelSettings->experimentalGameplayOverride;
		return $result;
	}

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 */
	public static function read(PacketSerializer $in) : self{
		//TODO: in the future we'll use promoted properties + named arguments for decoding, but for now we stick with
		//this shitty way to limit BC breaks (needs more R&D)
		$result = new self;
		$result->internalRead($in);
		return $result;
	}

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 */
	private function internalRead(PacketSerializer $in) : void{
		$this->seed = $in->getVarInt();
		$this->spawnSettings = SpawnSettings::read($in);
		$this->generator = $in->getVarInt();
		$this->worldGamemode = $in->getVarInt();
		$this->difficulty = $in->getVarInt();
		$this->spawnPosition = $in->getBlockPosition();
		$this->hasAchievementsDisabled = $in->getBool();
		$this->time = $in->getVarInt();
		$this->eduEditionOffer = $in->getVarInt();
		$this->hasEduFeaturesEnabled = $in->getBool();
		$this->eduProductUUID = $in->getString();
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
		$this->experiments = Experiments::read($in);
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
		$this->vanillaVersion = $in->getString();
		$this->limitedWorldWidth = $in->getLInt();
		$this->limitedWorldLength = $in->getLInt();
		$this->isNewNether = $in->getBool();
		$this->eduSharedUriResource = EducationUriResource::read($in);
		$this->experimentalGameplayOverride = $in->readOptional(Closure::fromCallable([$in, 'getBool']));
	}

	public function write(PacketSerializer $out) : void{
		$out->putVarInt($this->seed);
		$this->spawnSettings->write($out);
		$out->putVarInt($this->generator);
		$out->putVarInt($this->worldGamemode);
		$out->putVarInt($this->difficulty);
		$out->putBlockPosition($this->spawnPosition);
		$out->putBool($this->hasAchievementsDisabled);
		$out->putVarInt($this->time);
		$out->putVarInt($this->eduEditionOffer);
		$out->putBool($this->hasEduFeaturesEnabled);
		$out->putString($this->eduProductUUID);
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
		$this->experiments->write($out);
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
		$out->putString($this->vanillaVersion);
		$out->putLInt($this->limitedWorldWidth);
		$out->putLInt($this->limitedWorldLength);
		$out->putBool($this->isNewNether);
		($this->eduSharedUriResource ?? new EducationUriResource("", ""))->write($out);
		$out->writeOptional($this->experimentalGameplayOverride, Closure::fromCallable([$out, 'putBool']));
	}
}
