<?php

namespace MultiVersion\network\proto\v486;

use Closure;
use InvalidArgumentException;
use MultiVersion\network\proto\MVPacketSerializer;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\utils\BinaryDataException;

class v486PacketSerializer extends MVPacketSerializer{

	public function getSkin() : SkinData{
		$skinId = $this->getString();
		$skinPlayFabId = $this->getString();
		$skinResourcePatch = $this->getString();
		$skinData = $this->getSkinImage();
		$animationCount = $this->getLInt();
		$animations = [];
		for($i = 0; $i < $animationCount; ++$i){
			$skinImage = $this->getSkinImage();
			$animationType = $this->getLInt();
			$animationFrames = $this->getLFloat();
			$expressionType = $this->getLInt();
			$animations[] = new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType);
		}
		$capeData = $this->getSkinImage();
		$geometryData = $this->getString();
		$geometryDataVersion = $this->getString();
		$animationData = $this->getString();
		$capeId = $this->getString();
		$fullSkinId = $this->getString();
		$armSize = $this->getString();
		$skinColor = $this->getString();
		$personaPieceCount = $this->getLInt();
		$personaPieces = [];
		for($i = 0; $i < $personaPieceCount; ++$i){
			$pieceId = $this->getString();
			$pieceType = $this->getString();
			$packId = $this->getString();
			$isDefaultPiece = $this->getBool();
			$productId = $this->getString();
			$personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
		}
		$pieceTintColorCount = $this->getLInt();
		$pieceTintColors = [];
		for($i = 0; $i < $pieceTintColorCount; ++$i){
			$pieceType = $this->getString();
			$colorCount = $this->getLInt();
			$colors = [];
			for($j = 0; $j < $colorCount; ++$j){
				$colors[] = $this->getString();
			}
			$pieceTintColors[] = new PersonaPieceTintColor(
				$pieceType,
				$colors
			);
		}

		$premium = $this->getBool();
		$persona = $this->getBool();
		$capeOnClassic = $this->getBool();
		$isPrimaryUser = $this->getBool();

		return new SkinData(
			$skinId,
			$skinPlayFabId,
			$skinResourcePatch,
			$skinData,
			$animations,
			$capeData,
			$geometryData,
			$geometryDataVersion,
			$animationData,
			$capeId,
			$fullSkinId,
			$armSize,
			$skinColor,
			$personaPieces,
			$pieceTintColors,
			true,
			$premium,
			$persona,
			$capeOnClassic,
			$isPrimaryUser,
			$override ?? true
		);
	}

	public function putSkin(SkinData $skin) : void{
		$this->putString($skin->getSkinId());
		$this->putString($skin->getPlayFabId());
		$this->putString($skin->getResourcePatch());
		$this->putSkinImage($skin->getSkinImage());
		$this->putLInt(count($skin->getAnimations()));
		foreach($skin->getAnimations() as $animation){
			$this->putSkinImage($animation->getImage());
			$this->putLInt($animation->getType());
			$this->putLFloat($animation->getFrames());
			$this->putLInt($animation->getExpressionType());
		}
		$this->putSkinImage($skin->getCapeImage());
		$this->putString($skin->getGeometryData());
		$this->putString($skin->getGeometryDataEngineVersion());
		$this->putString($skin->getAnimationData());
		$this->putString($skin->getCapeId());
		$this->putString($skin->getFullSkinId());
		$this->putString($skin->getArmSize());
		$this->putString($skin->getSkinColor());
		$this->putLInt(count($skin->getPersonaPieces()));
		foreach($skin->getPersonaPieces() as $piece){
			$this->putString($piece->getPieceId());
			$this->putString($piece->getPieceType());
			$this->putString($piece->getPackId());
			$this->putBool($piece->isDefaultPiece());
			$this->putString($piece->getProductId());
		}
		$this->putLInt(count($skin->getPieceTintColors()));
		foreach($skin->getPieceTintColors() as $tint){
			$this->putString($tint->getPieceType());
			$this->putLInt(count($tint->getColors()));
			foreach($tint->getColors() as $color){
				$this->putString($color);
			}
		}
		$this->putBool($skin->isPremium());
		$this->putBool($skin->isPersona());
		$this->putBool($skin->isPersonaCapeOnClassic());
		$this->putBool($skin->isPrimaryUser());
	}

	private function getSkinImage() : SkinImage{
		$width = $this->getLInt();
		$height = $this->getLInt();
		$data = $this->getString();
		try{
			return new SkinImage($height, $width, $data);
		}catch(InvalidArgumentException $e){
			throw new PacketDecodeException($e->getMessage(), 0, $e);
		}
	}

	private function putSkinImage(SkinImage $image) : void{
		$this->putLInt($image->getWidth());
		$this->putLInt($image->getHeight());
		$this->putString($image->getData());
	}

	public function getItemStack(Closure $writeExtraCrapInTheMiddle) : ItemStack{
		$id = $this->getVarInt();
		if($id === 0){
			return ItemStack::null();
		}

		$count = $this->getLShort();
		$meta = $this->getUnsignedVarInt();

		$writeExtraCrapInTheMiddle($this);

		$blockRuntimeId = $this->getVarInt();
		$extraData = self::newDecoder($this->getString(), 0, $this->getContext());
		return (static function() use ($extraData, $id, $meta, $count, $blockRuntimeId) : ItemStack{
			$nbtLen = $extraData->getLShort();

			/** @var CompoundTag|null $compound */
			$compound = null;
			if($nbtLen === 0xffff){
				$nbtDataVersion = $extraData->getByte();
				if($nbtDataVersion !== 1){
					throw new PacketDecodeException("Unexpected NBT data version $nbtDataVersion");
				}
				$offset = $extraData->getOffset();
				try{
					$compound = (new LittleEndianNbtSerializer())->read($extraData->getBuffer(), $offset, 512)->mustGetCompoundTag();
				}catch(NbtDataException $e){
					throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
				}finally{
					$extraData->setOffset($offset);
				}
			}elseif($nbtLen !== 0){
				throw new PacketDecodeException("Unexpected fake NBT length $nbtLen");
			}

			$canPlaceOn = [];
			for($i = 0, $canPlaceOnCount = $extraData->getLInt(); $i < $canPlaceOnCount; ++$i){
				$canPlaceOn[] = $extraData->get($extraData->getLShort());
			}

			$canDestroy = [];
			for($i = 0, $canDestroyCount = $extraData->getLInt(); $i < $canDestroyCount; ++$i){
				$canDestroy[] = $extraData->get($extraData->getLShort());
			}

			$shieldBlockingTick = null;
			if($id === $extraData->shieldRuntimeId){
				$shieldBlockingTick = $extraData->getLLong();
			}

			if(!$extraData->feof()){
				throw new PacketDecodeException("Unexpected trailing extradata for network item $id");
			}

			return new ItemStack($id, $meta, $count, $blockRuntimeId, $compound, $canPlaceOn, $canDestroy, $shieldBlockingTick);
		})();
	}

	public function putItemStack(ItemStack $item, Closure $writeExtraCrapInTheMiddle) : void{
		if($item->getId() === 0){
			$this->putVarInt(0);

			return;
		}

		$this->putVarInt($item->getId());
		$this->putLShort($item->getCount());
		$this->putUnsignedVarInt($item->getMeta());

		$writeExtraCrapInTheMiddle($this);

		$this->putVarInt($item->getBlockRuntimeId());
		$this->putString((function() use ($item) : string{
			$extraData = self::newEncoder($this->getContext());

			$nbt = $item->getNbt();
			if($nbt !== null){
				$extraData->putLShort(0xffff);
				$extraData->putByte(1); //TODO: NBT data version (?)
				$extraData->put((new LittleEndianNbtSerializer())->write(new TreeRoot($nbt)));
			}else{
				$extraData->putLShort(0);
			}

			$extraData->putLInt(count($item->getCanPlaceOn()));
			foreach($item->getCanPlaceOn() as $entry){
				$extraData->putLShort(strlen($entry));
				$extraData->put($entry);
			}
			$extraData->putLInt(count($item->getCanDestroy()));
			foreach($item->getCanDestroy() as $entry){
				$extraData->putLShort(strlen($entry));
				$extraData->put($entry);
			}

			$blockingTick = $item->getShieldBlockingTick();
			if($item->getId() === $extraData->shieldRuntimeId){
				$extraData->putLLong($blockingTick ?? 0);
			}
			return $extraData->getBuffer();
		})());
	}

	public function getRecipeIngredient() : RecipeIngredient{
		$id = $this->getVarInt();
		if($id === 0){
			return new RecipeIngredient(new IntIdMetaItemDescriptor(0, 0), 0);
		}
		$meta = $this->getVarInt();
		if($meta === 0x7fff){
			$meta = -1;
		}
		$count = $this->getVarInt();
		return new RecipeIngredient(new IntIdMetaItemDescriptor($id, $meta), $count);
	}

	public function putRecipeIngredient(RecipeIngredient $item) : void{
		$descriptor = $item->getDescriptor();
		if($descriptor?->getTypeId() === IntIdMetaItemDescriptor::ID){
			/** @var IntIdMetaItemDescriptor $descriptor */
			if($descriptor->getId() === 0){
				$this->putVarInt(0);
			}else{
				$this->putVarInt($descriptor->getId());
				$this->putVarInt($descriptor->getMeta() & 0x7fff);
				$this->putVarInt($item->getCount());
			}
		}else{
			$this->putVarInt(0);
		}
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @return MetadataProperty[]
	 * @phpstan-return array<int, MetadataProperty>
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getEntityMetadata() : array{
		$count = $this->getUnsignedVarInt();
		$metadata = [];
		for($i = 0; $i < $count; ++$i){
			$key = $this->getUnsignedVarInt();
			$type = $this->getUnsignedVarInt();

			$metadata[$key] = $this->readMetadataProperty($type);
		}

		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		$flag2 <<= 1; // shift left by 1, leaving a 0 at the end
		$flag2 |= (($flag1 >> 63) & 1); // push the last bit from flag1 to the first bit of flag2

		$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1)); // don't include CAN_DASH and above
		$lastHalf = $flag1 & (~0 << (EntityMetadataFlags::CAN_DASH - 1)); // include everything after where CAN_DASH would be
		$lastHalf <<= 1; // shift left by 1, CAN_DASH is now 0
		$newFlag1 |= $lastHalf; // combine the two halves

		$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
		$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);

		return $metadata;
	}

	private function readMetadataProperty(int $type) : MetadataProperty{
		return match ($type) {
			ByteMetadataProperty::ID => ByteMetadataProperty::read($this),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($this),
			IntMetadataProperty::ID => IntMetadataProperty::read($this),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($this),
			StringMetadataProperty::ID => StringMetadataProperty::read($this),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($this),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($this),
			LongMetadataProperty::ID => LongMetadataProperty::read($this),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($this),
			default => throw new PacketDecodeException("Unknown entity metadata type " . $type),
		};
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param MetadataProperty[]                   $metadata
	 *
	 * @phpstan-param array<int, MetadataProperty> $metadata
	 */
	public function putEntityMetadata(array $metadata) : void{
		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		if($flag1 !== 0 || $flag2 !== 0){
			$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1));
			$lastHalf = $flag1 & (~0 << EntityMetadataFlags::CAN_DASH);
			$lastHalf >>= 1;
			$lastHalf &= PHP_INT_MAX;

			$newFlag1 |= $lastHalf;

			if($flag2 !== 0){
				$flag2 = $flag2Property->getValue();
				$newFlag1 ^= ($flag2 & 1) << 63;
				$flag2 >>= 1;
				$flag2 &= PHP_INT_MAX;

				$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
			}

			$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);
		}

		$this->putUnsignedVarInt(count($metadata));
		foreach($metadata as $key => $d){
			$this->putUnsignedVarInt($key);
			$this->putUnsignedVarInt($d->getTypeId());
			$d->write($this);
		}
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 *
	 * @throws BinaryDataException
	 */
	public function getAttributeList() : array{
		$list = [];
		$count = $this->getUnsignedVarInt();

		for($i = 0; $i < $count; ++$i){
			$min = $this->getLFloat();
			$max = $this->getLFloat();
			$current = $this->getLFloat();
			$default = $this->getLFloat();
			$id = $this->getString();

			$list[] = new Attribute($id, $min, $max, $current, $default, []);
		}

		return $list;
	}

	/**
	 * Writes a list of Attributes to the packet buffer using the standard format.
	 */
	public function putAttributeList(Attribute ...$attributes) : void{
		$this->putUnsignedVarInt(count($attributes));
		foreach($attributes as $attribute){
			$this->putLFloat($attribute->getMin());
			$this->putLFloat($attribute->getMax());
			$this->putLFloat($attribute->getCurrent());
			$this->putLFloat($attribute->getDefault());
			$this->putString($attribute->getId());
		}
	}
}