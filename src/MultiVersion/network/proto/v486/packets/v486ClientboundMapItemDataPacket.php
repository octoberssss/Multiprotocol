<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v486\packets;

use InvalidArgumentException;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Binary;

class v486ClientboundMapItemDataPacket extends ClientboundMapItemDataPacket{

	public static function fromLatest(ClientboundMapItemDataPacket $pk) : self{
		$npk = new self();
		$npk->mapId = $pk->mapId;
		$npk->type = $pk->type;
		$npk->dimensionId = $pk->dimensionId;
		$npk->isLocked = $pk->isLocked;
		$npk->parentMapIds = $pk->parentMapIds;
		$npk->scale = $pk->scale;
		$npk->trackedEntities = $pk->trackedEntities;
		$npk->decorations = $pk->decorations;
		$npk->xOffset = $pk->xOffset;
		$npk->yOffset = $pk->yOffset;
		$npk->colors = $pk->colors;
		return $npk;
	}


	protected function decodePayload(PacketSerializer $in) : void{
		$this->mapId = $in->getActorUniqueId();
		$this->type = $in->getUnsignedVarInt();
		$this->dimensionId = $in->getByte();
		$this->isLocked = $in->getBool();

		if(($this->type & self::BITFLAG_MAP_CREATION) !== 0){
			$count = $in->getUnsignedVarInt();
			for($i = 0; $i < $count; ++$i){
				$this->parentMapIds[] = $in->getActorUniqueId();
			}
		}

		if(($this->type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
			$this->scale = $in->getByte();
		}

		if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$object = new MapTrackedObject();
				$object->type = $in->getLInt();
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					$object->blockPosition = $in->getBlockPosition();
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					$object->actorUniqueId = $in->getActorUniqueId();
				}else{
					throw new PacketDecodeException("Unknown map object type $object->type");
				}
				$this->trackedEntities[] = $object;
			}

			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$icon = $in->getByte();
				$rotation = $in->getByte();
				$xOffset = $in->getByte();
				$yOffset = $in->getByte();
				$label = $in->getString();
				$color = Color::fromRGBA(Binary::flipIntEndianness($in->getUnsignedVarInt()));
				$this->decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
		}

		if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
			$width = $in->getVarInt();
			$height = $in->getVarInt();
			$this->xOffset = $in->getVarInt();
			$this->yOffset = $in->getVarInt();

			$count = $in->getUnsignedVarInt();
			if($count !== $width * $height){
				throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
			}

			$this->colors = MapImage::decode($in, $height, $width);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorUniqueId($this->mapId);

		$type = 0;
		if(($parentMapIdsCount = count($this->parentMapIds)) > 0){
			$type |= self::BITFLAG_MAP_CREATION;
		}
		if(($decorationCount = count($this->decorations)) > 0){
			$type |= self::BITFLAG_DECORATION_UPDATE;
		}
		if($this->colors !== null){
			$type |= self::BITFLAG_TEXTURE_UPDATE;
		}

		$out->putUnsignedVarInt($type);
		$out->putByte($this->dimensionId);
		$out->putBool($this->isLocked);

		if(($type & self::BITFLAG_MAP_CREATION) !== 0){
			$out->putUnsignedVarInt($parentMapIdsCount);
			foreach($this->parentMapIds as $parentMapId){
				$out->putActorUniqueId($parentMapId);
			}
		}

		if(($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
			$out->putByte($this->scale);
		}

		if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			$out->putUnsignedVarInt(count($this->trackedEntities));
			foreach($this->trackedEntities as $object){
				$out->putLInt($object->type);
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					$out->putBlockPosition($object->blockPosition);
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					$out->putActorUniqueId($object->actorUniqueId);
				}else{
					throw new InvalidArgumentException("Unknown map object type $object->type");
				}
			}

			$out->putUnsignedVarInt($decorationCount);
			foreach($this->decorations as $decoration){
				$out->putByte($decoration->getIcon());
				$out->putByte($decoration->getRotation());
				$out->putByte($decoration->getXOffset());
				$out->putByte($decoration->getYOffset());
				$out->putString($decoration->getLabel());
				$out->putUnsignedVarInt(Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		}

		if($this->colors !== null){
			$out->putVarInt($this->colors->getWidth());
			$out->putVarInt($this->colors->getHeight());
			$out->putVarInt($this->xOffset);
			$out->putVarInt($this->yOffset);

			$out->putUnsignedVarInt($this->colors->getWidth() * $this->colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang

			$this->colors->encode($out);
		}
	}
}