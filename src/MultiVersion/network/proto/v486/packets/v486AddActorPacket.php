<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;

class v486AddActorPacket extends AddActorPacket{

	public static function fromLatest(AddActorPacket $pk) : self{
		$npk = new self();
		$npk->actorUniqueId = $pk->actorUniqueId;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->type = $pk->type;
		$npk->position = $pk->position;
		$npk->motion = $pk->motion;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->headYaw = $pk->headYaw;
		$npk->attributes = $pk->attributes;
		$npk->metadata = $pk->metadata;
		$npk->links = $pk->links;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->type = $in->getString();
		$this->position = $in->getVector3();
		$this->motion = $in->getVector3();
		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();
		$this->headYaw = $in->getLFloat();

		$attrCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $attrCount; ++$i){
			$id = $in->getString();
			$min = $in->getLFloat();
			$current = $in->getLFloat();
			$max = $in->getLFloat();
			$this->attributes[] = new Attribute($id, $min, $max, $current, $current, []);
		}

		$this->metadata = $in->getEntityMetadata(); // TODO: convert back?

		$linkCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[] = $in->getEntityLink();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putString($this->type);
		$out->putVector3($this->position);
		$out->putVector3Nullable($this->motion);
		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
		$out->putLFloat($this->headYaw);

		$out->putUnsignedVarInt(count($this->attributes));
		foreach($this->attributes as $attribute){
			$out->putString($attribute->getId());
			$out->putLFloat($attribute->getMin());
			$out->putLFloat($attribute->getCurrent());
			$out->putLFloat($attribute->getMax());
		}

		$out->putEntityMetadata($this->metadata);

		$out->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$out->putEntityLink($link);
		}
	}
}
