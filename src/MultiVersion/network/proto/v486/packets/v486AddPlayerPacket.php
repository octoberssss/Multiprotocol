<?php

namespace MultiVersion\network\proto\v486\packets;

use MultiVersion\network\proto\static_resources\GenericItemTranslator;
use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use MultiVersion\network\proto\utils\NetItemConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;

class v486AddPlayerPacket extends AddPlayerPacket{

	public static function fromLatest(AddPlayerPacket $pk, IRuntimeBlockMapping $blockMapping, GenericItemTranslator $itemTranslator) : self{
		$npk = new self();
		$npk->uuid = $pk->uuid;
		$npk->username = $pk->username;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->platformChatId = $pk->platformChatId;
		$npk->position = $pk->position;
		$npk->motion = $pk->motion;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->headYaw = $pk->headYaw;
		$npk->item = NetItemConverter::convertToProtocol($pk->item, $blockMapping, $itemTranslator);
		$npk->gameMode = $pk->gameMode;
		$npk->metadata = $pk->metadata;
		$npk->syncedProperties = $pk->syncedProperties;
		$npk->abilitiesPacket = $pk->abilitiesPacket;
		$npk->links = $pk->links;
		$npk->deviceId = $pk->deviceId;
		$npk->buildPlatform = $pk->buildPlatform;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->uuid = $in->getUUID();
		$this->username = $in->getString();
		$in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->platformChatId = $in->getString();
		$this->position = $in->getVector3();
		$this->motion = $in->getVector3();
		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();
		$this->headYaw = $in->getLFloat();
		$this->item = ItemStackWrapper::read($in);
		$this->metadata = $in->getEntityMetadata();

		$packet = new v486AdventureSettingsPacket();
		$packet->decodePayload($in);

		$this->abilitiesPacket = UpdateAbilitiesPacket::create(
			new AbilitiesData(
				$packet->commandPermission,
				$packet->playerPermission,
				$packet->targetActorUniqueId,
				[]
			)
		);

		$linkCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i] = $in->getEntityLink();
		}

		$this->deviceId = $in->getString();
		$this->buildPlatform = $in->getLInt();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUUID($this->uuid);
		$out->putString($this->username);
		$out->putActorUniqueId($this->abilitiesPacket->getData()->getTargetActorUniqueId());
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putString($this->platformChatId);
		$out->putVector3($this->position);
		$out->putVector3Nullable($this->motion);
		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
		$out->putLFloat($this->headYaw);
		$this->item->write($out);
		$out->putEntityMetadata($this->metadata);

		$packet = v486AdventureSettingsPacket::create(
			0,
			$this->abilitiesPacket->getData()->getCommandPermission(),
			0,
			$this->abilitiesPacket->getData()->getPlayerPermission(),
			0,
			$this->abilitiesPacket->getData()->getTargetActorUniqueId()
		);
		$packet->encodePayload($out);

		$out->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$out->putEntityLink($link);
		}

		$out->putString($this->deviceId);
		$out->putLInt($this->buildPlatform);
	}
}
