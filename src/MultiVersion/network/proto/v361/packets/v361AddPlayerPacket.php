<?php

namespace MultiVersion\network\proto\v361\packets;

use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use MultiVersion\network\proto\utils\NetItemConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class v361AddPlayerPacket extends AddPlayerPacket{

	public int $actorUniqueId;
	public int $uvarint1 = 0;
	public int $uvarint2 = 0;
	public int $uvarint3 = 0;
	public int $uvarint4 = 0;
	public int $uvarint5 = 0;
	public int $long1 = 0;

	public static function fromLatest(AddPlayerPacket $pk, IRuntimeBlockMapping $blockMapping) : self{
		$npk = new self();
		$npk->uuid = $pk->uuid;
		$npk->username = $pk->username;
		$npk->actorUniqueId = $pk->actorRuntimeId;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->platformChatId = $pk->platformChatId;
		$npk->position = $pk->position;
		$npk->motion = $pk->motion;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->headYaw = $pk->headYaw;
		$npk->item = NetItemConverter::convertToProtocol($pk->item, $blockMapping);
		$npk->metadata = $pk->metadata;

		$npk->uvarint1 = 0;
		$npk->uvarint2 = $pk->abilitiesPacket->getData()->getCommandPermission();
		$npk->uvarint3 = 0;
		$npk->uvarint4 = $pk->abilitiesPacket->getData()->getPlayerPermission();
		$npk->uvarint5 = 0;

		$npk->long1 = $pk->actorRuntimeId;

		$npk->links = $pk->links;
		$npk->deviceId = $pk->deviceId;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->uuid = $in->getUUID();
		$this->username = $in->getString();
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->platformChatId = $in->getString();
		$this->position = $in->getVector3();
		$this->motion = $in->getVector3();
		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();
		$this->headYaw = $in->getLFloat();
		$this->item = ItemStackWrapper::read($in);
		$this->metadata = $in->getEntityMetadata();

		$this->uvarint1 = $in->getUnsignedVarInt();
		$this->uvarint2 = $in->getUnsignedVarInt();
		$this->uvarint3 = $in->getUnsignedVarInt();
		$this->uvarint4 = $in->getUnsignedVarInt();
		$this->uvarint5 = $in->getUnsignedVarInt();

		$this->long1 = $in->getLLong();

		$linkCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i] = $in->getEntityLink();
		}

		$this->deviceId = $in->getString();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUUID($this->uuid);
		$out->putString($this->username);
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putString($this->platformChatId);
		$out->putVector3($this->position);
		$out->putVector3Nullable($this->motion);
		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
		$out->putLFloat($this->headYaw);
		$this->item->write($out);
		$out->putEntityMetadata($this->metadata);

		$out->putUnsignedVarInt($this->uvarint1);
		$out->putUnsignedVarInt($this->uvarint2);
		$out->putUnsignedVarInt($this->uvarint3);
		$out->putUnsignedVarInt($this->uvarint4);
		$out->putUnsignedVarInt($this->uvarint5);

		$out->putLLong($this->long1);

		$out->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$out->putEntityLink($link);
		}

		$out->putString($this->deviceId);
	}
}
